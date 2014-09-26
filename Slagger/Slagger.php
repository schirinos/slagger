<?php
/**
 * Slagger - A Slim middleware that will automatically add swagger doc endpoints to your app 
 * using zircote's swagger annotation parser.
 *
 * @author      Sigfrido Chirinos
 * @link        https://github.com/schirinos/slagger
 * @package     Slagger
 * @author      Sigfrido Chirinos
 *
 * MIT LICENSE
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */
namespace Slagger; 

class Slagger extends \Slim\Middleware
{
    public function __construct($path, $scanPath, $options = array()) 
    {
        $this->path = $path;
        $this->scanPath = $scanPath;
        $this->options = array_merge(array(
            'output' => 'json'
        ), $options);
    }

    public function call()
    {
        //The Slim application
        $app = $this->app;

        // Swager Documentation
        $app->group($this->path, function () use ($app) {
            $swagger = new \Swagger\Swagger($this->scanPath);
            $resourceList = $swagger->getResourceList();
            
            $app->get('/', function () use ($swagger, $resourceList) {
                echo json_encode($resourceList);
            });

            foreach ($resourceList['apis'] as $value) {
                $app->get($value['path'], function () use ($swagger, $value) {
                    echo $swagger->getResource($value['path'], $this->options);
                });
            }
            
        });

        // Call next middleware
        $this->next->call();
    }
}
