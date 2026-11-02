<?php


namespace App\Controllers\Auth;

use App\Controllers\Controller;
use App\Web\Session;
use App\Web\ValidationHelper;
use Psr\Http\Message\ServerRequestInterface as Request;

abstract class AuthController extends Controller
{
    protected function checkRecaptcha(ValidationHelper $validator, Request $request)
    {
        $validator->callIf($this->getSetting('recaptcha_enabled') === 'on', function (Session $session) use (&$request) {
            $recaptcha = json_decode((string) file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret='.urlencode((string) $this->getSetting('recaptcha_secret_key')).'&response='.urlencode((string) param($request, 'recaptcha_token'))));

            // A failed verification must always be refused, an unreachable or
            // unparsable answer of the endpoint as well.
            if (!$recaptcha || !$recaptcha->success || (isset($recaptcha->score) && $recaptcha->score < 0.5)) {
                $session->alert(lang('recaptcha_failed'), 'danger');
                return false;
            }
            return true;
        });
        return $validator;
    }


    /**
     * Connects to LDAP server and logs in with service account (if configured)
     * @return \LDAP\Connection|resource|false
     */
    public function ldapConnect()
    {
        if (!extension_loaded('ldap')) {
            $this->logger->error('The LDAP extension is not loaded.');
            return false;
        }
        // Building LDAP URI
        $ldapSchema=(@is_string($this->config['ldap']['schema'])) ?
            strtolower($this->config['ldap']['schema']) : 'ldap';
        $ldapURI="$ldapSchema://".$this->config['ldap']['host'].':'.$this->config['ldap']['port'];

        // Connecting to LDAP server
        $this->logger->debug("Connecting to $ldapURI");
        $server = ldap_connect($ldapURI);
        if ($server) {
            ldap_set_option($server, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($server, LDAP_OPT_REFERRALS, 0);
            ldap_set_option($server, LDAP_OPT_NETWORK_TIMEOUT, 10);
        } else {
            $this->logger->error('LDAP-URI was not parseable');
            return false;
        }

        // Upgrade to StartTLS
        $useStartTLS = @is_bool($this->config['ldap']['useStartTLS']) ? $this->config['ldap']['useStartTLS'] : false;
        if (($useStartTLS === true) && (ldap_start_tls($server) === false)) {
            $this->logger->debug(ldap_error($server));
            $this->logger->error("Failed to establish secure LDAP swith StartTLS");
            return false;
        }

        // Authenticating LDAP service account (if configured)
        $serviceAccountFQDN= (@is_string($this->config['ldap']['service_account_dn'])) ?
            $this->config['ldap']['service_account_dn'] : null;
        if (is_string($serviceAccountFQDN)) {
            if (ldap_bind($server, $serviceAccountFQDN, $this->config['ldap']['service_account_password']) === false) {
                $this->logger->debug(ldap_error($server));
                $this->logger->error("Bind with service account ($serviceAccountFQDN) failed.");
                return false;
            }
        }

        return $server;
    }

    /**
     * Returns User's LDAP DN
     * @param  string  $username
     * @param \LDAP\Connection|resource $server LDAP Server Resource
     * @return string|null
     */
    protected function getLdapRdn(string $username, $server)
    {
        //Dynamic LDAP User Binding
        if (@is_string($this->config['ldap']['search_filter'])) {
            //Replace ???? with username
            $searchFilter = str_replace('????', ldap_escape($username, '', LDAP_ESCAPE_FILTER), $this->config['ldap']['search_filter']);
            $ldapAddributes = array('dn');
            $this->logger->debug("LDAP Search filter: $searchFilter");
            $ldapSearchResp = ldap_search(
                $server,
                $this->config['ldap']['base_domain'],
                $searchFilter,
                $ldapAddributes
            );
            if (!$ldapSearchResp) {
                $this->logger->debug(ldap_error($server));
                $this->logger->error("User LDAP search for user $username failed");
                return null;
            }
            if (ldap_count_entries($server, $ldapSearchResp) !== 1) {
                $this->logger->notice("LDAP search for $username not found or had multiple entries");
                return null;
            }
            $ldapEntry = ldap_first_entry($server, $ldapSearchResp);
            //Returns full DN
            $bindString = ldap_get_dn($server, $ldapEntry);
        } else {
            // Static LDAP Binding
            $bindString = ($this->config['ldap']['rdn_attribute'] ?? 'uid=').addslashes($username);
            if ($this->config['ldap']['user_domain'] !== null) {
                $bindString .= ','.$this->config['ldap']['user_domain'];
            }

            if ($this->config['ldap']['base_domain'] !== null) {
                $bindString .= ','.$this->config['ldap']['base_domain'];
            }
            //returns partial DN
        }
        return $bindString;
    }
}
