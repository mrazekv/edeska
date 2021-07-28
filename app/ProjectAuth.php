<?php
namespace App;

use Nette\Security\Passwords;
use Nette\Security\AuthenticationException;
use Nette\Security as NS;


class ProjectAuth implements NS\Authenticator
{
    public $database;

    /** @var Passwords */
	private $passwords;

    function __construct(\Nette\Database\Explorer $database, Passwords $passwords)
    {
        $this->database = $database;
        $this->passwords = $passwords;
    }

    function authenticate(string $user, string $password) : NS\IIdentity
    {
		$item = $this->database->table('users')->where('username', $user);
        $row = $item->fetch();

        if (!$row) {
            throw new AuthenticationException('User not found.');
        }

        if (!$this->passwords->verify($password, $row->password)) {
            throw new AuthenticationException('Invalid password.');
        }

		$item->update(["last_login"=>$this->database::literal('now()')]);
        return new NS\SimpleIdentity($row->username, $row->role, ['user' => $row->username, 'name' => $row->name]);
    }
	
	function changePassword($username, $old, $new)
	{
		$item = $this->database->table('users')->where('username', $username);
        $row = $item->fetch();

        if (!$this->passwords->verify($old, $row->password)) {
            throw new NS\AuthenticationException('Invalid password.');
        }
		
		$row->update(["password" => $this->passwords->hash($new)]);
		
		
		
	}
}