<?php

/* Copyright (C) 2010-2024, the Friendica project
 * SPDX-FileCopyrightText: 2010-2024 the Friendica project
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * The configuration defines "complex" dependencies inside Friendica
 * So this classes shouldn't be simple or their dependencies are already defined here.
 *
 * This kind of dependencies are NOT required to be defined here:
 *   - $a = new ClassA(new ClassB());
 *   - $a = new ClassA();
 *   - $a = new ClassA(Configuration $configuration);
 *
 * This kind of dependencies SHOULD be defined here:
 *   - $a = new ClassA();
 *     $b = $a->create();
 *
 *   - $a = new ClassA($creationPassedVariable);
 *
 */

use Dice\Dice;
use Friendica\App;
use Friendica\AppHelper;
use Friendica\AppLegacy;
use Friendica\Core\Cache;
use Friendica\Core\Config;
use Friendica\Core\Hooks\Capability\ICanCreateInstances;
use Friendica\Core\Hooks\Capability\ICanRegisterStrategies;
use Friendica\Core\Hooks\Model\DiceInstanceManager;
use Friendica\Core\PConfig;
use Friendica\Core\L10n;
use Friendica\Core\Lock;
use Friendica\Core\Session\Capability\IHandleSessions;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\Storage\Repository\StorageManager;
use Friendica\Database\Database;
use Friendica\Database\Definition\DbaDefinition;
use Friendica\Database\Definition\ViewDefinition;
use Friendica\Factory;
use Friendica\Core\Storage\Capability\ICanWriteToStorage;
use Friendica\Model\User\Cookie;
use Friendica\Model\Log\ParsedLogIterator;
use Friendica\Network;
use Friendica\Util;
use Psr\Log\LoggerInterface;

/**
 * @var string $basepath The base path of the Friendica installation without trailing slash
 */
$basepath = (function() {
	return dirname(__FILE__, 2);
})();

return [
	'*'                             => [
		// marks all class result as shared for other creations, so there's just
		// one instance for the whole execution
		'shared' => true,
	],
	\Friendica\Core\Addon\Capability\ICanLoadAddons::class => [
		'instanceOf' => \Friendica\Core\Addon\Model\AddonLoader::class,
		'constructParams' => [
			$basepath,
			[Dice::INSTANCE => Dice::SELF],
		],
	],
	'$basepath'                     => [
		'instanceOf'      => Util\BasePath::class,
		'call'            => [
			['getPath', [], Dice::CHAIN_CALL],
		],
		'constructParams' => [
			dirname(__FILE__, 2),
			$_SERVER
		]
	],
	Util\BasePath::class         => [
		'constructParams' => [
			dirname(__FILE__, 2),
			$_SERVER
		]
	],
	DiceInstanceManager::class   => [
		'constructParams' => [
			[Dice::INSTANCE => Dice::SELF],
		]
	],
	\Friendica\Core\Hooks\Util\StrategiesFileManager::class => [
		'constructParams' => [
			$basepath,
		],
		'call' => [
			['loadConfig'],
		],
	],
	ICanRegisterStrategies::class => [
		'instanceOf' => DiceInstanceManager::class,
		'constructParams' => [
			[Dice::INSTANCE => Dice::SELF],
		],
	],
	AppHelper::class => [
		'instanceOf' => AppLegacy::class,
	],
	ICanCreateInstances::class   => [
		'instanceOf' => DiceInstanceManager::class,
		'constructParams' => [
			[Dice::INSTANCE => Dice::SELF],
		],
	],
	Config\Util\ConfigFileManager::class => [
		'instanceOf' => Config\Factory\Config::class,
		'call'       => [
			['createConfigFileManager', [
				$basepath,
				$_SERVER,
			], Dice::CHAIN_CALL],
		],
	],
	Config\ValueObject\Cache::class => [
		'instanceOf' => Config\Factory\Config::class,
		'call'       => [
			['createCache', [], Dice::CHAIN_CALL],
		],
	],
	App\Mode::class              => [
		'call' => [
			['determineRunMode', [true, $_SERVER], Dice::CHAIN_CALL],
			['determine', [
				$basepath,
			], Dice::CHAIN_CALL],
		],
	],
	Config\Capability\IManageConfigValues::class => [
		'instanceOf' => Config\Model\DatabaseConfig::class,
		'constructParams' => [
			$_SERVER,
		],
	],
	PConfig\Capability\IManagePersonalConfigValues::class => [
		'instanceOf' => PConfig\Factory\PConfig::class,
		'call'       => [
			['create', [], Dice::CHAIN_CALL],
		]
	],
	DbaDefinition::class => [
		'constructParams' => [
			$basepath,
		],
		'call' => [
			['load', [false], Dice::CHAIN_CALL],
		],
	],
	ViewDefinition::class => [
		'constructParams' => [
			$basepath,
		],
		'call' => [
			['load', [false], Dice::CHAIN_CALL],
		],
	],
	Database::class                         => [
		'constructParams' => [
			[Dice::INSTANCE => Config\Model\ReadOnlyFileConfig::class],
		],
	],
	/**
	 * Creates the App\BaseURL
	 *
	 * Same as:
	 *   $baseURL = new App\BaseURL($configuration, $_SERVER);
	 */
	App\BaseURL::class             => [
		'constructParams' => [
			$_SERVER,
		],
	],
	'$hostname'                    => [
		'instanceOf' => App\BaseURL::class,
		'constructParams' => [
			$_SERVER,
		],
		'call' => [
			['getHost', [], Dice::CHAIN_CALL],
		],
	],
	Cache\Type\AbstractCache::class => [
		'constructParams' => [
			[Dice::INSTANCE => '$hostname'],
		],
	],
	App\Page::class => [
		'constructParams' => [
			$basepath,
		],
	],
	\Psr\Log\LoggerInterface::class                                    => [
		'instanceOf' => \Friendica\Core\Logger\Factory\Logger::class,
		'call'       => [
			['create', [], Dice::CHAIN_CALL],
		],
	],
	\Friendica\Core\Logger\Type\SyslogLogger::class                    => [
		'instanceOf' => \Friendica\Core\Logger\Factory\SyslogLogger::class,
		'call'       => [
			['create', [], Dice::CHAIN_CALL],
		],
	],
	\Friendica\Core\Logger\Type\StreamLogger::class                    => [
		'instanceOf' => \Friendica\Core\Logger\Factory\StreamLogger::class,
		'call'       => [
			['create', [], Dice::CHAIN_CALL],
		],
	],
	\Friendica\Core\Logger\Capability\IHaveCallIntrospections::class => [
		'instanceOf'      => \Friendica\Core\Logger\Util\Introspection::class,
		'constructParams' => [
			\Friendica\Core\Logger\Capability\IHaveCallIntrospections::IGNORE_CLASS_LIST,
		],
	],
	'$devLogger'                                                       => [
		'instanceOf' => \Friendica\Core\Logger\Factory\StreamLogger::class,
		'call'       => [
			['createDev', [], Dice::CHAIN_CALL],
		],
	],
	Cache\Capability\ICanCache::class => [
		'instanceOf' => Cache\Factory\Cache::class,
		'call'       => [
			['createLocal', [], Dice::CHAIN_CALL],
		],
	],
	Cache\Capability\ICanCacheInMemory::class => [
		'instanceOf' => Cache\Factory\Cache::class,
		'call'       => [
			['createLocal', [], Dice::CHAIN_CALL],
		],
	],
	Lock\Capability\ICanLock::class => [
		'instanceOf' => Lock\Factory\Lock::class,
		'call'       => [
			['create', [], Dice::CHAIN_CALL],
		],
	],
	App\Arguments::class => [
		'instanceOf' => App\Arguments::class,
		'call' => [
			['determine', [$_SERVER, $_GET], Dice::CHAIN_CALL],
		],
	],
	\Friendica\Core\System::class => [
		'constructParams' => [
			$basepath,
		],
	],
	App\Router::class => [
		'constructParams' => [
			$_SERVER,
			__DIR__ . '/routes.config.php',
			[Dice::INSTANCE => Dice::SELF],
			null
		],
	],
	L10n::class => [
		'constructParams' => [
			$_SERVER, $_GET
		],
	],
	IHandleSessions::class => [
		'instanceOf' => \Friendica\Core\Session\Factory\Session::class,
		'call' => [
			['create', [$_SERVER], Dice::CHAIN_CALL],
			['start', [], Dice::CHAIN_CALL],
		],
	],
	IHandleUserSessions::class => [
		'instanceOf' => \Friendica\Core\Session\Model\UserSession::class,
	],
	Cookie::class => [
		'constructParams' => [
			$_COOKIE
		],
	],
	ICanWriteToStorage::class => [
		'instanceOf' => StorageManager::class,
		'call' => [
			['getBackend', [], Dice::CHAIN_CALL],
		],
	],
	\Friendica\Core\KeyValueStorage\Capability\IManageKeyValuePairs::class => [
		'instanceOf' => \Friendica\Core\KeyValueStorage\Factory\KeyValueStorage::class,
		'call' => [
			['create', [], Dice::CHAIN_CALL],
		],
	],
	Network\HTTPClient\Capability\ICanSendHttpRequests::class => [
		'instanceOf' => Network\HTTPClient\Factory\HttpClient::class,
		'call'       => [
			['createClient', [], Dice::CHAIN_CALL],
		],
	],
	ParsedLogIterator::class => [
		'constructParams' => [
			[Dice::INSTANCE => Util\ReversedFileReader::class],
		]
	],
	\Friendica\Core\Worker\Repository\Process::class => [
		'constructParams' => [
			$_SERVER
		],
	],
	App\Request::class => [
		'constructParams' => [
			$_SERVER
		],
	],
	\Psr\Clock\ClockInterface::class => [
		'instanceOf' => Util\Clock\SystemClock::class
	],
	\Friendica\Module\Special\HTTPException::class => [
		'constructParams' => [
			$_SERVER
		],
	],
	\Friendica\Module\Api\ApiResponse::class => [
		'constructParams' => [
			$_SERVER,
			$_GET['callback'] ?? '',
		],
	],
];
