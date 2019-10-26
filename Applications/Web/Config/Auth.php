<?php

namespace Web\Config;

/**
 * Config Auth.
 */
class Auth
{
    /**
     * Auth user name
     * @var string
     */
    public static $username = 'admin';

    /**
     * Auth password encrypted
     * @var string
     */
    public static $encrypted = 'bf6da20b3027832dc6d83b7b6153ce12';

    /**
     * Auth password salt
     * @var string
     */
    public static $salt = 'YCv6yX';

    /**
     * Auth key prefix
     * @var string
     */
    public static $prefix = 'auth_';

    /**
     * Auth expire no action in 600 seconds
     * @var string
     */
    public static $expire = '600';
}
