<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Twitter\Account;

use Friendica\Database\DBA;
use Friendica\Module\BaseApi;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Profile;

/**
 * Update user profile
 */
class UpdateProfile extends BaseApi
{
	protected function post(array $request = [])
	{
		$this->checkAllowedScope(BaseApi::SCOPE_WRITE);
		$uid = BaseApi::getCurrentUserID();

		$api_user = DI::twitterUser()->createFromUserId($uid, true)->toArray();

		if (!empty($request['name'])) {
			DBA::update('profile', ['name' => $request['name']], ['uid' => $uid]);
			DBA::update('user', ['username' => $request['name']], ['uid' => $uid]);
			Contact::update(['name' => $request['name']], ['uid' => $uid, 'self' => 1]);
			Contact::update(['name' => $request['name']], ['id' => $api_user['id']]);
		}

		if (isset($request['description'])) {
			DBA::update('profile', ['about' => $request['description']], ['uid' => $uid]);
			Contact::update(['about' => $request['description']], ['uid' => $uid, 'self' => 1]);
			Contact::update(['about' => $request['description']], ['id' => $api_user['id']]);
		}

		Contact::updateSelfFromUserID($uid, true);

		Profile::publishUpdate($uid);

		$skip_status = $this->getRequestValue($request, 'skip_status', false);

		$user_info = DI::twitterUser()->createFromUserId($uid, $skip_status)->toArray();

		// "verified" isn't used here in the standard
		unset($user_info["verified"]);

		// "uid" is only needed for some internal stuff, so remove it from here
		unset($user_info['uid']);

		$this->response->addFormattedContent('user', ['user' => $user_info], $this->parameters['extension'] ?? null);
	}
}
