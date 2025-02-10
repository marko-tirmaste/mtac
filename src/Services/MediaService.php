<?php

declare(strict_types=1);

namespace Seeru\Mtac\Services;

use Vdisain\Plugins\Interfaces\Support\Logger;
use Vdisain\Plugins\Interfaces\Support\Collection;
use Vdisain\Plugins\Interfaces\Services\MediaService as BaseMediaService;

defined('ABSPATH') or die;

class MediaService extends BaseMediaService
{
    /**
     * Downloads the product images
     *
     * MTac removed additional image links, so to keep gallery images, we override this method
     * to not remove existing gallery images.
     *
     * @param int $productId Product ID
     * @param Collection|array $images Array of image URLs
     */
    public function download(int $productId, Collection|array $images): ?array
    {
        if (empty($images)) {
            return null;
        }

        $removedAttachmentIds = $images instanceof Collection ? $this->removeDeletedImages($productId, $images) : null;
        $featuredImageId = get_post_thumbnail_id($productId);

        if (!empty($removedAttachmentIds) && !empty($featuredImageId) && $removedAttachmentIds->contains($featuredImageId)) {
            delete_post_meta($productId, '_thumbnail_id');
            $featuredImageId = null;
        }

        $gallery = vi_collect(
            array_filter([
                $featuredImageId,
                ...explode(',', get_post_meta($productId, '_product_image_gallery', true) ?? '')
            ])
        )
            ->filter(function (int|string|null $id): bool {
                $type = get_post_type($id);
                return !empty($id) && !empty($type) && $type === 'attachment';
            })
            // ->filter(fn (int|string|null $id): bool => !empty($id) && get_post_type($id) === 'attachment')
            ->mapWithKeys(fn (int|string $id, int $key): array => [(int) $id => [
                'id' => (int) $id,
                'order' => $key + 1
            ]])
            ->toArray();

        $batchImages = []; // Initialize an array to hold the batch-downloaded images

        foreach ($images as $i => $image) {
            if (empty($image)) {
                continue;
            }

            $url = is_string($image) ? trim($image) : trim($image->url);
            $attachment = $this->repo->findWithKey('_vdai_original', $url);

            if (is_object($image) && $image->status === 'trash') {
                if (!empty($attachment)) {
                    wp_delete_attachment($attachment->ID, true);
                    unset($gallery[$attachment->ID]);
                }

                continue;
            }

            if (!empty($attachment)) {
                // $gallery[] = $attachment->ID;
                $gallery[$attachment->ID] = [
                    'id' => $attachment->ID,
                    'order' => is_object($image) && !empty($image->order) ? $image->order : $i + 1
                ];

                if (file_exists(get_attached_file($attachment->ID))) {
                    continue; // Attachment already exists, skip
                }
            }

            if (empty($url)) {
                continue;
            }

            $imgHeaders = @get_headers(str_replace(" ", "%20", $url))[0];
            if ($imgHeaders == 'HTTP/1.1 404 Not Found') {
                continue;
            }

            // Validate extension
            preg_match('/[^\?]+\.(jpg|jpeg|gif|png|jfif|webp)$/i', $url, $matches);
            if (empty($matches)) {
                Logger::warn("[{$productId}] {$url} is not an image.");
                continue;
            }

            $filename = basename($matches[0]);
            while (isset($batchImages[$filename])) {
                $filename = time() . '_' . $filename;
            }

            $batchImages[$filename] = $url;

            break; // For now, download one image at the time
        }

        // Batch download images using parallel HTTP requests
        $responses = $this->parallelImageDownload($batchImages);

        $i = 1;
        // Loop through downloaded images and sideload them
        foreach ($responses as $filename => $response) {
            if (is_wp_error($response) || !is_array($response) || empty($response['body'])) {
                Logger::warn("[{$productId}] Failed to download image: {$batchImages[$filename]}");
                if (vi()->isVerbose(3)) {
                    Logger::dump($response);
                }
                continue;
            }

            $filePath = wp_upload_dir()['path'] . '/' . $filename;
            file_put_contents($filePath, $response['body']);

            $filetype = wp_check_filetype($filePath);

            if (!in_array($filetype['type'], ['image/jpeg', 'image/png', 'image/gif', 'image/jfif+xml', 'image/webp'])) {
                Logger::warn("[{$productId}] Invalid image type: {$filetype['type']} for file: {$filename}");

                @unlink($filePath);
                continue;
            }

            $thumbnailId = media_handle_sideload([
                'name' => basename($filePath),
                'tmp_name' => $filePath
            ], $productId);


            if (is_wp_error($thumbnailId)) {
                file_put_contents(__DIR__ . '/error.log', print_r($thumbnailId->get_error_message(), true), FILE_APPEND);
                Logger::warn("[{$productId}] Failed to sideload image: {$batchImages[$filename]}");

                @unlink($filePath);
                continue;
            }

            if ($images instanceof Collection) {
                $image = $images->first(fn (mixed $image): bool => (is_string($image) && $image === $batchImages[$filename]) || $image->url === $batchImages[$filename]);

                if ($image instanceof AttachmentContract) {
                    update_post_meta($thumbnailId, $image->getIdentifierKey(), $image->getIdentifier());
                }

                $gallery[$thumbnailId] = [
                    'id' => $thumbnailId,
                    'order' => is_object($image) && !empty($image->order) ? $image->order : $i
                ];
            } else {
                $gallery[$thumbnailId] = [
                    'id' => $thumbnailId,
                    'order' => $i
                ];
            }

            update_post_meta($thumbnailId, '_vdai_original', $batchImages[$filename]);
            update_post_meta($thumbnailId, '_type', 'image');

            @unlink($filePath);
            $i++;
        }

        $gallery = array_filter($gallery);

        if (!empty($gallery)) {
            usort($gallery, function (array $a, array $b): int {
                return $a['order'] <=> $b['order'];
            });
        }

        $newFeaturedImage = array_shift($gallery);
        if (!empty($newFeaturedImage) && $newFeaturedImage['id'] !== $featuredImageId) {
            // file_put_contents(__DIR__ . '/test.log', print_r($newFeaturedImage['id'], true), FILE_APPEND);
            set_post_thumbnail($productId, $newFeaturedImage['id']);
        }

        // file_put_contents(__DIR__ . '/test1.log', print_r(implode(',', array_column($gallery, 'id')), true), FILE_APPEND);
        update_post_meta($productId, '_product_image_gallery', implode(',', array_column($gallery, 'id')));

        return [$featuredImageId, ...$gallery];
    }


    // public function download(int $productId, Collection|array $images): ?array
    // {
    //     if (empty($images)) {
    //         return null;
    //     }

    //     $featuredImageId = get_post_thumbnail_id($productId);

    //     $gallery = vi_collect(
    //         array_filter([
    //             $featuredImageId,
    //             ...explode(',', get_post_meta($productId, '_product_image_gallery', true) ?? '')
    //         ])
    //     )
    //         ->filter(function (int|string|null $id): bool {
    //             $type = get_post_type($id);
    //             return !empty($id) && !empty($type) && $type === 'attachment';
    //         })
    //         ->mapWithKeys(fn(int|string $id, int $key): array => [
    //             (int) $id => [
    //                 'id' => (int) $id,
    //                 'order' => $key + 1
    //             ]
    //         ])
    //         ->toArray();

    //     $batchImages = []; // Initialize an array to hold the batch-downloaded images

    //     /** @var \Seeru\Mtac\Models\Image $image */
    //     foreach ($images as $i => $image) {
    //         if (empty($url)) {
    //             continue;
    //         }

    //         $url = trim(string: $image->url);
    //         $attachment = $this->repo->findWithKey('_vdai_original', $url);

    //         if (!empty($attachment)) {
    //             $gallery[$attachment->ID] = [
    //                 'id' => $attachment->ID,
    //                 'order' => $i + 1
    //             ];

    //             if (file_exists(get_attached_file($attachment->ID))) {
    //                 continue; // Attachment already exists, skip
    //             }
    //         }

    //         if (empty($url)) {
    //             continue;
    //         }

    //         $imgHeaders = @get_headers(str_replace(" ", "%20", $url))[0];
    //         if ($imgHeaders == 'HTTP/1.1 404 Not Found') {
    //             continue;
    //         }

    //         // Validate extension
    //         preg_match('/[^\?]+\.(jpg|jpeg|gif|png|jfif|webp)$/i', $url, $matches);
    //         if (empty($matches)) {
    //             continue;
    //         }

    //         $filename = basename($matches[0]);
    //         while (isset($batchImages[$filename])) {
    //             $filename = time() . '_' . $filename;
    //         }

    //         $batchImages[$filename] = $url;
    //     }

    //     // Batch download images using parallel HTTP requests
    //     $responses = $this->parallelImageDownload($batchImages);

    //     $i = 1;
    //     // Loop through downloaded images and sideload them
    //     foreach ($responses as $filename => $response) {
    //         if (is_wp_error($response) || !is_array($response) || empty($response['body'])) {
    //             Logger::warn("[{$productId}] Failed to download image: {$batchImages[$filename]}");
    //             if (vi()->isVerbose(3)) {
    //                 Logger::dump($response);
    //             }
    //             continue;
    //         }

    //         $filePath = wp_upload_dir()['path'] . '/' . $filename;
    //         file_put_contents($filePath, $response['body']);

    //         $filetype = wp_check_filetype($filePath);

    //         if (!in_array($filetype['type'], ['image/jpeg', 'image/png', 'image/gif', 'image/jfif+xml', 'image/webp'])) {
    //             Logger::warn("[{$productId}] Invalid image type: {$filetype['type']} for file: {$filename}");

    //             @unlink($filePath);
    //             continue;
    //         }

    //         $thumbnailId = media_handle_sideload([
    //             'name' => basename($filePath),
    //             'tmp_name' => $filePath
    //         ], $productId);


    //         if (is_wp_error($thumbnailId)) {
    //             file_put_contents(__DIR__ . '/error.log', print_r($thumbnailId->get_error_message(), true), FILE_APPEND);
    //             Logger::warn("[{$productId}] Failed to sideload image: {$batchImages[$filename]}");

    //             @unlink($filePath);
    //             continue;
    //         }

    //         $gallery[$thumbnailId] = [
    //             'id' => $thumbnailId,
    //             'order' => $i
    //         ];

    //         update_post_meta($thumbnailId, '_vdai_original', $batchImages[$filename]);
    //         update_post_meta($thumbnailId, '_type', 'image');

    //         @unlink($filePath);
    //         $i++;
    //     }

    //     $gallery = array_filter($gallery);

    //     if (!empty($gallery)) {
    //         usort($gallery, function (array $a, array $b): int {
    //             return $a['order'] <=> $b['order'];
    //         });
    //     }

    //     $newFeaturedImage = array_shift($gallery);
    //     if (!empty($newFeaturedImage) && $newFeaturedImage['id'] !== $featuredImageId) {
    //         set_post_thumbnail($productId, $newFeaturedImage['id']);
    //     }

    //     update_post_meta($productId, '_product_image_gallery', implode(',', array_column($gallery, 'id')));

    //     return [$featuredImageId, ...$gallery];
    // }
}