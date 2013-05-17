<?php
/**
 * Slagger - A Slim middleware to generate swagger documentation json automatically from an application routes
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

class APIDoc extends \Slim\Middleware
{
    /**
     * Swagger settings for overall api
     * @var string
     */
    public $settings;

    /**
     * The route pattern to get the api documentation
     * @var string
     */
    public $docPath;

    /**
     * Constructor
     * @param string $docPath The route pattern to get the api documentation
     * @param string $settings The swagger settings for the whole api
     */
    public function __construct($docPath, $settings) 
    {
        // Set docpath and settings
        $this->docPath = $docPath;
        $this->settings = $settings;
    }

    /**
     * Setup initial swagger options
     */
    public function setup() 
    {
        // Merge settings with default
        $this->settings = array_merge([
            'apiVersion' => $this->app->config('version'),
            'swaggerVersion' =>  $this->app->config('swaggerversion'),
            'basePath' => $this->app->environment()['slim.url_scheme']."://".$_SERVER['HTTP_HOST'].$this->app->environment()['SCRIPT_NAME'],
            'resourcePath' => "/api"
        ], $this->settings);
    }

    /**
     * Invoked route middleware
     */
    public function call()
    {  
        // The Slim application
        $app = $this->app;

        // If this the special documentation generating route
        // then we short circuit the app and output the swagger documentation json
        if ($app->request()->getPathInfo() === $this->docPath) {

            // Merge default settings, with ones passed in constructor
            $this->setup();
            
            // The Environment object
            $env = $app->environment();

            // Generate base api info
            $apiData = $this->settings;
            
            // Init empty array
            $apiData['apis'] = [];

            // Iterate through named routes
            foreach ($app->router()->getNamedRoutes() as $routeName => $route) {

                // Init array to store the path paramater names 
                $path_param_names = [];

                // Get the pattern for the current route
                $pattern = $route->getPattern();

                // Convert path paramters in the pattern to swagger style params
                $swagger_pattern = preg_replace_callback('#:([\w]+)\+?#', function ($match) use (&$path_param_names) {
                    // Store the parameter name, (minus the colon)
                    $path_param_names[] = $match[1];

                    // Return parameter formatted for swagger
                    return "{".$match[1]."}";
                }, $pattern);
                
                // Init empty array to store all the HTTP operations for the route
                $operations = [];

                // Iterate through the HTTP methods for the route. 
                // This is how we build the "operations" array for the swagger doc
                foreach ($route->getHttpMethods() as $method) {

                    // Get path parameter options
                    $route_path_parms = $this->routeDoc[$routeName]['PATH'];

                    // Init array to store path parameters
                    $path_params = [];

                    // Iterate through path paramaters extracted from the route
                    foreach ($path_param_names as $param_name) {
                        // Set defaults and add new parameter
                        array_push($path_params, 
                            array_merge([
                                "name" => $param_name,
                                "description" => $param_name,
                                "paramType" => "path",
                                "required" => true,
                                "allowMultiple" => false,
                                "dataType" => "String"
                            ], (isset($route_path_parms[$param_name]) ? $route_path_parms[$param_name] : []))
                        );
                    }

                    // Get the querystring parameter options
                    $route_query_parms = $this->routeDoc[$routeName]['GET'];

                    // Init array to store querystring parameters
                    $query_parms = [];

                    // Only process if it is not empty and an array
                    if (!empty($route_query_parms) && is_array($route_query_parms)) {
                        foreach ($route_query_parms as $value) {
                            array_push($query_parms, 
                                [
                                    "name" => (!empty($value['name'])) ? $value['name'] : "",
                                    "description" => (!empty($value['description'])) ? $value['description'] : "", 
                                    "paramType" => "query",
                                    "required" => (isset($value['required']) && is_bool($value['required'])) ? $value['required'] : true,
                                    "allowMultiple" => (!empty($value['allowMultiple']) && is_bool($value['allowMultiple'])) ? $value['allowMultiple'] : false,
                                    "dataType" => (!empty($value['dataType'])) ? $value['dataType'] : "String"
                                ]
                            );
                        }
                    }

                    // We only need either post or body params
                    // Body params are for json payloads and POST are submitted by forms
                    // Post params will take precedent
                    if (!empty($this->routeDoc[$routeName]['POST']) && is_array($this->routeDoc[$routeName]['POST'])) {
                       $route_body_params =  $this->routeDoc[$routeName]['POST'];
                       $body_param_type = 'form';
                    } else if (!empty($this->routeDoc[$routeName]['BODY']) && is_array($this->routeDoc[$routeName]['BODY'])) {
                        $route_body_params =  $this->routeDoc[$routeName]['BODY'];
                        $body_param_type = 'body';
                    } else {
                        $route_body_params = [];
                        $body_param_type = 'form'; 
                    }

                    // Init array to story querystring parameters
                    $body_parms = [];

                    // Only process if it is not empty and an array
                    if (!empty($route_body_params) && is_array($route_body_params)) {
                        // Iterate through body parameters
                        foreach ($route_body_params as $value) {
                            // Set defaults and add new parameter
                            array_push($body_parms, 
                                array_merge([
                                    "name" => "",
                                    "description" => "", 
                                    "paramType" => $body_param_type,
                                    "required" => true,
                                    "allowMultiple" => false,
                                    "dataType" => "String"
                                ], $value)
                            );
                        }
                    }

                    // Add a new operation definition merging in all the parameter definitions.
                    $operations[] = [
                        "httpMethod" => $method,
                        "summary" =>  (!empty($this->routeDoc[$routeName]['summary'])) ? $this->routeDoc[$routeName]['summary'] : $route->getName(),
                        "responseClass" => (!empty($this->routeDoc[$routeName]['responseClass'])) ? $this->routeDoc[$routeName]['responseClass'] : "void",
                        "errorResponses" => (!empty($this->routeDoc[$routeName]['errorResponses'])) ? $this->routeDoc[$routeName]['errorResponses'] : "",
                        "nickname" => $route->getName(),
                        "parameters" => array_merge($path_params, $query_parms, $body_parms)
                    ];

                    // For now only get the first of the http methods.
                    // We probably shouldn't have more than one HTTP method per named route
                    break;
                }

                // Now we construct the overall route details
                $route_details = [
                    "path" => $swagger_pattern,
                    "description" => $route->getName() . " action",
                    "operations" => $operations
                ];

                // Add new route details
                array_push($apiData['apis'], $route_details);
            }
          
            // output as json
            $app->response()['Content-Type'] = 'application/json';
            $app->response()->body(json_encode($apiData, JSON_UNESCAPED_SLASHES));

        } else {
           // Otherwise we call the next middleware and let the app continue
            $this->next->call(); 
        }
    }


    /**
     * Setup initial swagger options
     * @param string $name The named route to add documentation for
     * @param array $options The swagger doc options for the route
     * @return function
     */
    public function routeDoc($name, $options) 
    {
        // Add route documentation
        $this->routeDoc[$name] = array_merge([
            "PATH" => [],
            "GET" => []
        ], $options);

        // Needs to return a function, make it do nothing
        return function (\Slim\Route $route) {};
    }
}
