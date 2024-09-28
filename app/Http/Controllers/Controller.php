<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="Dictionary API Documentation",
 *      description="API to search words in a dictionary",
 *      @OA\Contact(
 *          email="support@example.com"
 *      )
 * )
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 * )
 */

abstract class Controller
{
    //
}
