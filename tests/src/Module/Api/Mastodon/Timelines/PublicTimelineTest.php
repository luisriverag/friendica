<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Module\Api\Mastodon\Timelines;

use Friendica\Test\ApiTestCase;

class PublicTimelineTest extends ApiTestCase
{
	/**
	 * Test the api_statuses_public_timeline() function.
	 *
	 * @return void
	 */
	public function testApiStatusesPublicTimeline()
	{
		self::markTestIncomplete('Needs PublicTimeline to not set header during call (like at BaseApi::setLinkHeader');

		/*
		$_REQUEST['max_id']          = 10;
		$_REQUEST['conversation_id'] = 1;
		$result                      = api_statuses_public_timeline('json');
		self::assertNotEmpty($result['status']);
		foreach ($result['status'] as $status) {
			self::assertStatus($status);
		}
		*/
	}

	/**
	 * Test the api_statuses_public_timeline() function with the exclude_replies parameter.
	 *
	 * @return void
	 */
	public function testApiStatusesPublicTimelineWithExcludeReplies()
	{
		self::markTestIncomplete('Needs PublicTimeline to not set header during call (like at BaseApi::setLinkHeader');

		/*
		$_REQUEST['max_id']          = 10;
		$_REQUEST['exclude_replies'] = true;
		$result                      = api_statuses_public_timeline('json');
		self::assertNotEmpty($result['status']);
		foreach ($result['status'] as $status) {
			self::assertStatus($status);
		}
		*/
	}

	/**
	 * Test the api_statuses_public_timeline() function with a negative page parameter.
	 *
	 * @return void
	 */
	public function testApiStatusesPublicTimelineWithNegativePage()
	{
		self::markTestIncomplete('Needs PublicTimeline to not set header during call (like at BaseApi::setLinkHeader');

		/*
		$_REQUEST['page'] = -2;
		$result           = api_statuses_public_timeline('json');
		self::assertNotEmpty($result['status']);
		foreach ($result['status'] as $status) {
			self::assertStatus($status);
		}
		*/
	}

	/**
	 * Test the api_statuses_public_timeline() function with an unallowed user.
	 *
	 * @return void
	 */
	public function testApiStatusesPublicTimelineWithUnallowedUser()
	{
		self::markTestIncomplete('Needs PublicTimeline to not set header during call (like at BaseApi::setLinkHeader');

		// $this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		// BasicAuth::setCurrentUserID();
		// api_statuses_public_timeline('json');
	}

	/**
	 * Test the api_statuses_public_timeline() function with an RSS result.
	 *
	 * @return void
	 */
	public function testApiStatusesPublicTimelineWithRss()
	{
		self::markTestIncomplete('Needs PublicTimeline to not set header during call (like at BaseApi::setLinkHeader');

		// $result = api_statuses_public_timeline('rss');
		// self::assertXml($result, 'statuses');
	}
}
