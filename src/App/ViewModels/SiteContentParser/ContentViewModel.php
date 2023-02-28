<?php

namespace Uzwebline\Linecms\App\ViewModels\SiteContentParser;

use Uzwebline\Linecms\App\Entities\ParsedContent;
use Uzwebline\Linecms\App\ViewModels\BaseViewModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Ramsey\Uuid\Uuid;

class ContentViewModel extends BaseViewModel
{
    public $id;
    public $title;
    public $link;
    public $image;
    public $local_image;
    public $published_at;

    protected $parsed_content;

    public function __construct(ParsedContent $parsed_content = null)
    {
        $this->parsed_content = $parsed_content;

        if (!is_null($parsed_content)) {
            $this->id = $parsed_content->id;
            $this->title = $parsed_content->title;
            $this->link = $parsed_content->link;
            $this->image = $parsed_content->img;
            $this->local_image = $parsed_content->local_image;
            $this->published_at = Carbon::parse($parsed_content->date)->format('d.m.Y');
        }
    }

    public function getImageUrl(?int $width = null, ?int $height = null, bool $force = true)
    {
        if (!$this->image || !$this->parsed_content)
            return null;

        if (!$this->local_image) {
            try {
                $image_id = Uuid::uuid4()->getHex()->toString();
                $ext = pathinfo($this->image, PATHINFO_EXTENSION);
                $path = storage_path('app/public/parsed_images/' . $image_id . '.' . $ext);
                file_put_contents($path, file_get_contents($this->image));
                $this->local_image = $image_id . '.' . $ext;
                $this->parsed_content->update([
                    'local_image' => $this->local_image
                ]);
            } catch (\Exception $exception) {
                return $this->image;
            }
        }


        $prefix = "";

        if ($force) {
            $prefix = "f_";
        }

        if ($width && $height) {
            $path = '/parsed_images/' . $prefix . $width . '_' . $height . '_' . $this->local_image;
            if (!Storage::disk('public')->exists($path)) {
                try {
                    $image_contents = Storage::disk('public')->get('/parsed_images/' . $this->local_image);
                    $img = Image::make($image_contents);
                } catch (\Exception $exception) {
                    return null;
                }
                if ($force) {
                    $img->fit($width, $height, function ($constraint) {
                        //$constraint->upsize();
                    })
                        ->resizeCanvas($width, $height, 'center', false)
                        ->save(storage_path('app/public/' . $path));
                } else {
                    $img->fit($width, $height, function ($constraint) {
                        $constraint->upsize();
                    })->save(storage_path('app/public/' . $path));
                }
            }
            return Storage::disk('public')->url($path);
        } elseif ($height) {
            $path = '/parsed_images/' . $prefix . 'x_' . $height . '_' . $this->local_image;
            if (!Storage::disk('public')->exists($path)) {
                try {
                    $image_contents = Storage::disk('public')->get('/parsed_images/' . $this->local_image);
                    $img = Image::make($image_contents);
                } catch (\Exception $exception) {
                    return null;
                }
                if ($force) {
                    $img->fit(null, $height, function ($constraint) {
                        /*$constraint->aspectRatio();
                        $constraint->upsize();*/
                    })
                        ->resizeCanvas(null, $height, 'center', false)
                        ->save(storage_path('app/public/' . $path));
                } else {
                    $img->fit(null, $height, function ($constraint) {
                        $constraint->upsize();
                    })->save(storage_path('app/public/' . $path));
                }

            }
            return Storage::disk('public')->url($path);
        } elseif ($width) {
            $path = '/parsed_images/' . $prefix . $width . '_x_' . $this->local_image;
            if (!Storage::disk('public')->exists($path)) {
                try {
                    $image_contents = Storage::disk('public')->get('/parsed_images/' . $this->local_image);
                    $img = Image::make($image_contents);
                } catch (\Exception $exception) {
                    return null;
                }
                if ($force) {
                    $img->fit($width, null, function ($constraint) {
                        /*$constraint->aspectRatio();
                        $constraint->upsize();*/
                    })
                        ->resizeCanvas($width, null, 'center', false)
                        ->save(storage_path('app/public/' . $path));
                } else {
                    $img->fit($width, null, function ($constraint) {
                        $constraint->upsize();
                    })->save(storage_path('app/public/' . $path));
                }
            }
            return Storage::disk('public')->url($path);
        } else {
            return Storage::disk('public')->url('/parsed_images/' . $this->local_image);
        }
    }
}
