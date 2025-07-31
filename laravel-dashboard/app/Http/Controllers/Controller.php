<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="Job Scraper API",
 *     version="1.0.0",
 *     description="API for managing companies and job postings scraped from LinkedIn",
 *     @OA\Contact(
 *         email="admin@jobscraper.com"
 *     )
 * )
 * @OA\Server(
 *     url="/api",
 *     description="API Server"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="ApiKeyAuth",
 *     type="apiKey",
 *     in="header",
 *     name="X-API-Key",
 *     description="API Key for authentication"
 * )
 * @OA\Response(
 *     response="UnauthorizedResponse",
 *     description="Unauthorized - Invalid or missing API key",
 *     @OA\JsonContent(
 *         @OA\Property(property="error", type="string", example="Unauthorized"),
 *         @OA\Property(property="message", type="string", example="Valid API key is required. Include X-API-Key header or api_key parameter.")
 *     )
 * )
 */
abstract class Controller
{
    //
}
