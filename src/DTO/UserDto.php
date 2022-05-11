<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class UserDto
{
    /**
     * @Assert\NotBlank(message="Name is mandatory")
     * @Assert\Email( message="Invalid email address" )
     */
    public $username;

    /**
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Length(min=6)
     */
    public $password;

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @param string $username
     * @return self
     */
    public function setUsername(string $username): UserDto
    {
        $this->username = $username;
        return $this;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }


    /**
     * @param string $password
     *
     * @return self
     */
    public function setPassword(string $password): UserDto
    {
        $this->password = $password;

        return $this;
    }
}
