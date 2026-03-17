<?php

namespace App\Services;

use App\Interfaces\Repositories\ProductsRepositoryInterface;
use App\Models\Media;
use App\Services\FileUploadService;

class ProductsService extends BaseService
{
    public function __construct(
        ProductsRepositoryInterface $repository,
        protected FileUploadService $fileUploadService
    ) {
        parent::__construct($repository);
    }

    public function storeProduct(array $data, $coverFile = null)
    {
        if ($coverFile) {
            $media = $this->fileUploadService->upload($coverFile, 'products', 'public', [
                'width' => 500,
                'height' => 500,
                'crop' => true
            ]);
            $data['cover'] = $media->path;
        } else {
            // Unset if cover is present as null or UploadedFile in validated data
            unset($data['cover']);
        }

        return $this->repository->create($data);
    }

    public function updateProduct($id, array $data, $coverFile = null)
    {
        if ($coverFile) {
            $product = $this->repository->find($id);
            
            // Delete old cover if exists
            if ($product->cover) {
                $oldMedia = Media::where('path', $product->cover)->first();
                if ($oldMedia) {
                    $this->fileUploadService->delete($oldMedia);
                }
            }

            $media = $this->fileUploadService->upload($coverFile, 'products', 'public', [
                'width' => 500,
                'height' => 500,
                'crop' => true
            ]);
            $data['cover'] = $media->path;
        } else {
            // Prevent overwriting existing cover with null from request
            unset($data['cover']);
        }

        return $this->repository->update($id, $data);
    }

    public function delete($id)
    {
        $product = $this->repository->find($id);

        if ($product->cover) {
            $media = Media::where('path', $product->cover)->first();
            if ($media) {
                $this->fileUploadService->delete($media);
            }
        }

        return $this->repository->delete($id);
    }
}