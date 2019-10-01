<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Unit\StickinessResolvers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;

class NoHasUserGuard implements Guard
{
    /**
     * @var \Illuminate\Contracts\Auth\Authenticatable
     */
    protected $user;

    /**
     * Determine if the current user is authenticated.
     *
     * @return bool
     */
    public function check()
    {
        return (bool)$this->user;
    }

    /**
     * Determine if the current user is a guest.
     *
     * @return bool
     */
    public function guest()
    {
        return !$this->user;
    }

    /**
     * Get the currently authenticated user.
     *
     * @return null|\Illuminate\Contracts\Auth\Authenticatable
     */
    public function user()
    {
        return $this->user;
    }

    /**
     * Get the ID for the currently authenticated user.
     *
     * @return null|int|string
     */
    public function id()
    {
        return $this->user() && $this->user()->getAuthIdentifier();
    }

    /**
     * Validate a user's credentials.
     *
     * @param  array $credentials
     * @return bool
     */
    public function validate(array $credentials = [])
    {
        return false;
    }

    /**
     * Set the current user.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     */
    public function setUser(Authenticatable $user)
    {
        $this->user = $user;
    }
}
