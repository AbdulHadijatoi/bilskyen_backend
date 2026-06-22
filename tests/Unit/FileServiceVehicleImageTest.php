<?php

namespace Tests\Unit;

use App\Services\FileService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileServiceVehicleImageTest extends TestCase
{
    private FileService $fileService;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->fileService = $this->app->make(FileService::class);
    }

    public function test_large_jpeg_is_converted_to_webp_under_target_size(): void
    {
        if (! function_exists('imagewebp')) {
            $this->markTestSkipped('WebP encoding is not available in this PHP build.');
        }

        $file = $this->createTestJpeg(3200, 2400, 95);
        $targetBytes = (int) config('images.vehicle.target_max_bytes', 819200);

        $result = $this->fileService->uploadVehicleImage($file);

        $this->assertStringEndsWith('.webp', $result['path']);
        Storage::disk('public')->assertExists($result['path']);
        $this->assertLessThanOrEqual($targetBytes, Storage::disk('public')->size($result['path']));
    }

    public function test_output_dimensions_do_not_exceed_configured_max_long_edge(): void
    {
        if (! function_exists('imagewebp')) {
            $this->markTestSkipped('WebP encoding is not available in this PHP build.');
        }

        $maxEdge = (int) config('images.vehicle.max_long_edge', 2048);
        $file = $this->createTestJpeg(4000, 3000, 90);

        $result = $this->fileService->uploadVehicleImage($file);
        [$width, $height] = $this->readImageDimensions(Storage::disk('public')->path($result['path']));

        $this->assertLessThanOrEqual($maxEdge, max($width, $height));
    }

    public function test_vehicle_upload_creates_smaller_thumbnail(): void
    {
        if (! function_exists('imagewebp')) {
            $this->markTestSkipped('WebP encoding is not available in this PHP build.');
        }

        $file = $this->createTestJpeg(2800, 2100, 92);
        $result = $this->fileService->uploadVehicleImage($file);

        $this->assertNotNull($result['thumbnail_path']);
        Storage::disk('public')->assertExists($result['thumbnail_path']);

        $mainSize = Storage::disk('public')->size($result['path']);
        $thumbSize = Storage::disk('public')->size($result['thumbnail_path']);

        $this->assertLessThan($mainSize, $thumbSize);

        $thumbMaxEdge = (int) config('images.vehicle.thumbnail_max_long_edge', 480);
        [$thumbWidth, $thumbHeight] = $this->readImageDimensions(
            Storage::disk('public')->path($result['thumbnail_path'])
        );
        $this->assertLessThanOrEqual($thumbMaxEdge, max($thumbWidth, $thumbHeight));
    }

    public function test_animated_gif_is_stored_without_webp_conversion(): void
    {
        $file = $this->createAnimatedGifUpload();

        $result = $this->fileService->uploadVehicleImage($file);

        $this->assertStringEndsWith('.gif', $result['path']);
        Storage::disk('public')->assertExists($result['path']);
    }

    private function createTestJpeg(int $width, int $height, int $quality = 95): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'veh_img_');
        $image = imagecreatetruecolor($width, $height);

        for ($y = 0; $y < $height; $y += 40) {
            for ($x = 0; $x < $width; $x += 40) {
                $color = imagecolorallocate(
                    $image,
                    ($x * 3 + $y) % 255,
                    ($x * 7 + $y) % 255,
                    ($x * 11 + $y) % 255
                );
                imagefilledrectangle(
                    $image,
                    $x,
                    $y,
                    min($x + 39, $width - 1),
                    min($y + 39, $height - 1),
                    $color
                );
            }
        }

        imagejpeg($image, $path, $quality);
        imagedestroy($image);

        return new UploadedFile($path, 'vehicle.jpg', 'image/jpeg', null, true);
    }

    private function createAnimatedGifUpload(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'veh_gif_');
        $gif = "GIF89a\x01\x00\x01\x00\x80\x00\x00\xFF\xFF\xFF\x00\x00\x00!\xF9\x04\x01\x00\x00\x00\x00,\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02D\x01\x00;";
        $gif .= 'NETSCAPE2.0';
        file_put_contents($path, $gif);

        return new UploadedFile($path, 'vehicle.gif', 'image/gif', null, true);
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function readImageDimensions(string $absolutePath): array
    {
        $info = getimagesize($absolutePath);
        $this->assertNotFalse($info);

        return [(int) $info[0], (int) $info[1]];
    }
}
