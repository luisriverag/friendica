<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Core\Session\Factory;

use Friendica\App;
use Friendica\Core\Cache\Capability\ICanCache;
use Friendica\Core\Cache\Enum;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Session\Capability\IHandleSessions;
use Friendica\Core\Session\Type;
use Friendica\Core\Session\Handler;
use Friendica\Database\Database;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Factory for creating a valid Session for this run
 */
class Session
{
	/** @var string The plain, PHP internal session management */
	const HANDLER_NATIVE = 'native';
	/** @var string Using the database for session management */
	const HANDLER_DATABASE = 'database';
	/** @var string Using the cache for session management */
	const HANDLER_CACHE = 'cache';

	const HANDLER_DEFAULT = self::HANDLER_DATABASE;

	/**
	 * @param App\Mode            $mode
	 * @param App\BaseURL         $baseURL
	 * @param IManageConfigValues $config
	 * @param Database            $dba
	 * @param ICanCache           $cache
	 * @param LoggerInterface     $logger
	 * @param Profiler            $profiler
	 * @param array               $server
	 *
	 * @return IHandleSessions
	 */
	public function createSession(App\Mode $mode, App\BaseURL $baseURL, IManageConfigValues $config, Database $dba, ICanCache $cache, LoggerInterface $logger, Profiler $profiler, array $server = [])
	{
		$profiler->startRecording('session');
		$session = null;

		try {
			if ($mode->isInstall() || $mode->isBackend()) {
				$session = new Type\Memory();
			} else {
				$session_handler = $config->get('system', 'session_handler', self::HANDLER_DEFAULT);
				$handler         = null;

				switch ($session_handler) {
					case self::HANDLER_DATABASE:
						$handler = new Handler\Database($dba, $logger, $server);
						break;
					case self::HANDLER_CACHE:
						// In case we're using the db as cache driver, use the native db session, not the cache
						if ($config->get('system', 'cache_driver') === Enum\Type::DATABASE) {
							$handler = new Handler\Database($dba, $logger, $server);
						} else {
							$handler = new Handler\Cache($cache, $logger);
						}
						break;
				}

				$session = new Type\Native($baseURL, $handler);
			}
		} finally {
			$profiler->stopRecording();
			return $session;
		}
	}
}
