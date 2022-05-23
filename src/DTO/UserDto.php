<?php

namespace App\DTO;

use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

class UserDto
{
    /**
     * @var string
     *
     * @Assert\NotBlank(message="Поле пользователя не должно быть пустым")
     * @Assert\Email( message="Invalid email address" )
     * @Serializer\Type("string")
     */
    public $username;

    /**
     * @var string
     *
     * @Assert\NotBlank(message="Поле пароля не должно быть пустым")
     * @Assert\Length(min=6, minMessage="Пароль должен состоять минимум из 6 символов")
     * @Serializer\Type("string")
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
