<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Services\NhlResultParser;
use Google\ApiCore\ApiException;
use Google\ApiCore\ValidationException;
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
            $result = [];

            if ($imageContent !== '') {
                $credentials = config('services.google_vision.credentials');
                if (! is_string($credentials) || ! is_file($credentials) || ! is_readable($credentials)) {
                    return 'Google Vision: Die Service-Account-Datei fehlt oder ist nicht lesbar. Bitte GOOGLE_APPLICATION_CREDENTIALS prüfen.';
                }
                $key = json_decode(file_get_contents($credentials), true, 512, JSON_THROW_ON_ERROR);
                if (! is_array($key) || ($key['type'] ?? null) !== 'service_account'
                    || empty($key['client_email']) || empty($key['private_key'])) {
                    return 'Google Vision: Bitte eine vollständige Service-Account-JSON-Schlüsseldatei hinterlegen.';
                }
                $imageAnnotator = new ImageAnnotatorClient(['credentials' => $key]);
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
        } catch (ValidationException|\InvalidArgumentException|\JsonException $exception) {
            return 'Google Vision: Die Service-Account-Datei konnte nicht geladen werden. Bitte eine gültige JSON-Schlüsseldatei hinterlegen.';
        } catch (GoogleException|ApiException $googleException) {
            return 'Received exception: '.$googleException->getMessage();
        } finally {
            $imageAnnotator?->close();
        }

        return 'No image supplied.';
    }
}
