<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Console;

use Friendica\App\Mode;
use Friendica\Core\L10n;
use Friendica\Model\Contact;

/**
 * tool to block an account from the node
 *
 * With this tool, you can block an account in such a way, that no postings
 * or comments this account writes are accepted to the node.
 */
class GlobalCommunityBlock extends \Asika\SimpleConsole\Console
{
	protected $helpOptions = ['h', 'help', '?'];

	/**
	 * @var Mode
	 */
	private $appMode;
	/**
	 * @var \Friendica\Core\L10n
	 */
	private $l10n;

	protected function getHelp()
	{
		$help = <<<HELP
console globalcommunityblock - Block remote profile from interacting with this node
Usage
	bin/console globalcommunityblock <profile_url> [<reason>] [-h|--help|-?] [-v]

Description
	Blocks an account in such a way that no postings or comments this account writes are accepted to this node.
	You can provide a optional reason for the block.

Options
    -h|--help|-? Show help information
    -v           Show more debug information.
HELP;
		return $help;
	}

	public function __construct(Mode $appMode, L10n $l10n, $argv = null)
	{
		parent::__construct($argv);

		$this->appMode = $appMode;
		$this->l10n = $l10n;
	}

	protected function doExecute(): int
	{
		if ($this->getOption('v')) {
			$this->out('Class: ' . __CLASS__);
			$this->out('Arguments: ' . var_export($this->args, true));
			$this->out('Options: ' . var_export($this->options, true));
		}

		if (count($this->args) == 0) {
			$this->out($this->getHelp());
			return 0;
		}

		if (count($this->args) > 2) {
			throw new \Asika\SimpleConsole\CommandArgsException('Too many arguments');
		}

		if ($this->appMode->isInstall()) {
			throw new \RuntimeException('Database isn\'t ready or populated yet');
		}

		$contact_id = Contact::getIdForURL($this->getArgument(0));
		if (!$contact_id) {
			throw new \RuntimeException($this->l10n->t('Could not find any contact entry for this URL (%s)', $this->getArgument(0)));
		}

		$block_reason = $this->getArgument(1);
		if(Contact::block($contact_id, $block_reason)) {
			$this->out($this->l10n->t('The contact has been blocked from the node'));
		} else {
			throw new \RuntimeException('The contact block failed.');
		}

		return 0;
	}
}
