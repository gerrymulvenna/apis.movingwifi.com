# apis.movingwifi.com
Showcasing some examples of authenticating to and interacting with different APIs

## PURPOSE

Illustrations of how to get some common API services working with relatively simple self-contained PHP. 
Each example establishes the authentication, makes one or two API calls and handles their response(s).


## INSTALLATION

For each service you want to try, you'll need to create a `credentials.php` script in its folder, where
the following variables are defined with your unique credentials for the service:

$client_id
$client_secret
$redirect_uri
