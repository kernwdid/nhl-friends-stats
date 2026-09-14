<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Services\NhlResultParser;
use Google\ApiCore\ApiException;
use Google\Cloud\Core\Exception\GoogleException;
use Google\Cloud\Vision\V1\AnnotateImageRequest;
use Google\Cloud\Vision\V1\BatchAnnotateImagesRequest;
use Google\Cloud\Vision\V1\Client\ImageAnnotatorClient;
use Google\Cloud\Vision\V1\Feature;
use Google\Cloud\Vision\V1\Feature\Type;
use Google\Cloud\Vision\V1\Image;

class VisionController extends Controller
{
    public function getNHLResultFromImage(string $imageContent): array|string
    {
        $imageAnnotator = null;
        try {
            putenv('GOOGLE_APPLICATION_CREDENTIALS='.base_path('gc_config.json'));

            $result = [];

            if ($imageContent !== '') {
                $imageAnnotator = new ImageAnnotatorClient;
                $batch = $imageAnnotator->batchAnnotateImages(new BatchAnnotateImagesRequest([
                    'requests' => [new AnnotateImageRequest([
                        'image' => new Image(['content' => $imageContent]),
                        'features' => [new Feature(['type' => Type::TEXT_DETECTION])],
                    ])],
                ]));
                $response = $batch->getResponses()[0] ?? null;
                if ($response === null || ($response->hasError() && $response->getError()->getCode() !== 0)) {
                    return 'Image text detection failed.';
                }
                $texts = $response->getTextAnnotations();
                foreach ($texts as $key => $text) {
                    if ($key === 0) {
                        continue;
                    }
                    $vertices = [];
                    foreach ($text->getBoundingPoly()?->getVertices() ?? [] as $vertex) {
                        $vertices[] = [$vertex->getX(), $vertex->getY()];
                    }
                    $result[] = ['text' => $text->getDescription(), 'vertices' => $vertices];
                }

                return app(NhlResultParser::class)->parse($result, Team::pluck('id', 'abbreviation')->all());
            }
        } catch (GoogleException|ApiException $googleException) {
            return 'Received exception: '.$googleException->getMessage();
        } finally {
            $imageAnnotator?->close();
        }

        return 'No image supplied.';
    }
}
