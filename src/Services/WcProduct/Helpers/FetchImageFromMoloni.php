<?php

namespace MoloniOn\Services\WcProduct\Helpers;

use MoloniOn\Context;

class FetchImageFromMoloni
{
    private $img;
    private $imgTitle;

    public function __construct(string $img, ?string $imgTitle = '')
    {
        $this->img = $img;
        $this->imgTitle = $imgTitle;
    }

    public function get()
    {
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $imageUrl = Context::configs()->get('media_api_url') . $this->img;
        $uploadDir = wp_upload_dir();
        $imgRequest = wp_remote_get($imageUrl);

        if (is_wp_error($imgRequest)) {
            return 0;
        }

        $image_data = $imgRequest['body'];
        $filename = basename($imageUrl);

        if (wp_mkdir_p($uploadDir['path'])) {
            $file = $uploadDir['path'] . '/' . $filename;
        } else {
            $file = $uploadDir['basedir'] . '/' . $filename;
        }

        file_put_contents($file, $image_data);

        $wpFiletype = wp_check_filetype($filename, null);

        $attachment = [
            'post_mime_type' => $wpFiletype['type'],
            'post_title' => $this->imgTitle,
            'post_content' => '',
            'post_status' => 'inherit'
        ];

        $imageId = wp_insert_attachment($attachment, $file);
        $attachData = wp_generate_attachment_metadata($imageId, $file);

        wp_update_attachment_metadata($imageId, $attachData);
        update_post_meta($imageId, '_molonion_file_name', $this->img);

        return $imageId;
    }
}
