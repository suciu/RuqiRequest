    /**
     * @param        string     $url_segs
     * @param array  array      $params
     * @param string string     $http_method
     *
     * @return mixed
     * @throws \Exception
     */
	private function _send_request( $url_segs, $params = array(), $http_method = 'get' )
	{
		// Initialize and configure the request
		$req = curl_init( API_ENDPOINT.$url_segs );

		curl_setopt( $req, CURLOPT_USERAGENT, Ruqi::USERAGENT );
		curl_setopt( $req, CURLOPT_HTTPAUTH, CURLAUTH_BASIC );
		curl_setopt( $req, CURLOPT_USERPWD, $this->apiID.':'.$this->apiKey );
		curl_setopt( $req, CURLOPT_RETURNTRANSFER, TRUE );

		// Are we using POST or DELETE? Adjust the request accordingly
		if( $http_method == Ruqi::SP_HTTP_METHOD_POST ) {
			curl_setopt( $req, CURLOPT_HTTPHEADER, array('Content-Type: application/json') );
			curl_setopt( $req, CURLOPT_POST, TRUE );
			curl_setopt( $req, CURLOPT_POSTFIELDS, json_encode($params) );
		}
		if( $http_method == Ruqi::SP_HTTP_METHOD_DELETE ) {
			curl_setopt( $req, CURLOPT_CUSTOMREQUEST, "DELETE" );
		}

		// Get the response, clean the request and return the data
		$response = curl_exec( $req );
		$http_status = curl_getinfo( $req, CURLINFO_HTTP_CODE );

		curl_close( $req );

        // Everything when fine
        if( $http_status == 200 )
        {
            // Decode JSON by default
            if( $this->decode )
                return json_decode( $response );
            else
                return $response;
        }

        // Some error occurred
        $data = json_decode( $response );

        // The error was provided by serverpilot
        if( property_exists( $data, 'error' ) && property_exists( $data->error, 'message' ) )
            throw new Exception($data->error->message, $http_status);

        // No error as provided, pick a default
        switch( $http_status )
        {
            case 400:
                throw new Exception('We couldn\'t understand your request. Typically missing a parameter or header.', $http_status);
            break;
            case 401:
                throw new Exception('Either no authentication credentials were provided or they are invalid.', $http_status);
            break;
            case 402:
                throw new Exception('Method is restricted to users on the Coach or Business plan.', $http_status);
            break;
            case 403:
                throw new Exception('Forbidden.', $http_status);
            break;
            case 404:
                throw new Exception('You requested a resource that does not exist.', $http_status);
            break;
            case 409:
                throw new Exception('Typically when trying creating a resource that already exists.', $http_status);
            break;
            case 500:
                throw new Exception('Something unexpected happened on our end.', $http_status);
            break;
            default:
                throw new Exception('Unknown error.', $http_status);
                break;
        }
	}
