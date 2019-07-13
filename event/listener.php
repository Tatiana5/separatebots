<?php
/**
*
* @package phpBB Extension - Separate bots in legend
* @copyright (c) 2019 Татьяна5
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/
namespace tatiana5\separatebots\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
* Event listener
*/
class listener implements EventSubscriberInterface
{
	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var \phpbb\user */
	protected $user;

	protected $user_online_link;

	/**
	 * Constructor
	 * @param \phpbb\auth\auth                   $auth
	 * @param \phpbb\user                        $user
	*/
	public function __construct(\phpbb\auth\auth $auth, \phpbb\user $user)
	{
		$this->auth = $auth;
		$this->user = $user;

		$this->user_online_link_bots = [];
	}

	/**
	 * Assign functions defined in this class to event listeners in the core
	 *
	 * @return array
	 * @static
	 * @access public
	*/
	static public function getSubscribedEvents()
	{
		return array(
			'core.obtain_users_online_string_before_modify'	=> 'separate_bots',
			'core.obtain_users_online_string_modify'		=> 'template_bots',
		);
	}

	public function separate_bots($event)
	{
		if (sizeof($event['online_users']))
		{
			$rowset = $event['rowset'];

			$user_online_link = $this->user_online_link_bots = [];

			foreach ($rowset as $row)
			{
				// User is logged in and therefore not a guest
				if ($row['user_id'] != ANONYMOUS)
				{
					if (isset($online_users['hidden_users'][$row['user_id']]))
					{
						$row['username'] = '<em>' . $row['username'] . '</em>';
					}

					if (!isset($online_users['hidden_users'][$row['user_id']]) || $auth->acl_get('u_viewonline') || $row['user_id'] === $user->data['user_id'])
					{
						if ($row['user_type'] <> USER_IGNORE)
						{
							$user_online_link[$row['user_id']] = get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']);
						}
						else
						{
							$this->user_online_link_bots[$row['user_id']] = get_username_string('no_profile', $row['user_id'], $row['username'], $row['user_colour']);
						}
					}
				}
			}

			$event['user_online_link'] = $user_online_link;
		}
	}

	public function template_bots($event)
	{
		if (sizeof($this->user_online_link_bots))
		{
			$online_userlist_bots = implode(', ', $this->user_online_link_bots);
			$event['online_userlist'] .= '<br />' . $this->user->lang['G_BOTS'] . $this->user->lang['COLON'] . ' ' . $online_userlist_bots;
		}
	}
}
