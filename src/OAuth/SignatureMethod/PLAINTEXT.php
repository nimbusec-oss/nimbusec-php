<?php 
namespace Nimbusec\OAuth\SignatureMethod;

use Nimbusec\OAuth;
use Nimbusec\OAuth\Util;

class PLAINTEXT extends OAuth\SignatureMethod
{
    public function get_name()
    {
        return "PLAINTEXT";
    }

  /**
   * oauth_signature is set to the concatenated encoded values of the Consumer Secret and
   * Token Secret, separated by a '&' character (ASCII code 38), even if either secret is
   * empty. The result MUST be encoded again.
   *   - Chapter 9.4.1 ("Generating Signatures")
   *
   * Please note that the second encoding MUST NOT happen in the SignatureMethod, as
   * OAuthRequest handles this!
   */
  public function build_signature($request, $consumer, $token)
  {
      $key_parts = array(
      $consumer->secret,
      ($token) ? $token->secret : ""
    );

      $key_parts = Util::urlencode_rfc3986($key_parts);
      $key = implode('&', $key_parts);
      $request->base_string = $key;

      return $key;
  }
}
