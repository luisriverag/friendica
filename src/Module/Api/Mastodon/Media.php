<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon;

use Friendica\Core\Logger;
use Friendica\DI;
use Friendica\Model\Attach;
use Friendica\Model\Contact;
use Friendica\Model\Photo;
use Friendica\Model\Post;
use Friendica\Module\BaseApi;

/**
 * @see https://docs.joinmastodon.org/methods/statuses/media/
 */
class Media extends BaseApi
{
	protected function post(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_WRITE);
		$uid = self::getCurrentUserID();

		$request = $this->getRequest([
			'file'        => [], // The file to be attached, using multipart form data.
			'thumbnail'   => [], // The custom thumbnail of the media to be attached, using multipart form data.
			'description' => '', // A plain-text description of the media, for accessibility purposes.
			'focus'       => '', // Two floating points (x,y), comma-delimited ranging from -1.0 to 1.0
		], $request);

		Logger::info('Photo post', ['request' => $request, 'files' => $_FILES]);

		if (empty($_FILES['file'])) {
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
		}

		$type = Post\Media::getType($_FILES['file']['type']);

		if (in_array($type, [Post\Media::IMAGE, Post\Media::UNKNOWN])) {
			$media = Photo::upload($uid, $_FILES['file'], '', null, null, '', '', $request['description']);
			if (empty($media)) {
				$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
			}

			Logger::info('Uploaded photo', ['media' => $media]);

			$this->jsonExit(DI::mstdnAttachment()->createFromPhoto($media['id']));
		} else {
			$tempFileName = $_FILES['file']['tmp_name'];
			$fileName     = basename($_FILES['file']['name']);
			$fileSize     = intval($_FILES['file']['size']);
			$maxFileSize  = DI::config()->get('system', 'maxfilesize');

			if ($fileSize <= 0) {
				@unlink($tempFileName);
				$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
			}

			if ($maxFileSize && $fileSize > $maxFileSize) {
				@unlink($tempFileName);
				$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
			}

			$id = Attach::storeFile($tempFileName, self::getCurrentUserID(), $fileName, $_FILES['file']['type'], '<' . Contact::getPublicIdByUserId(self::getCurrentUserID()) . '>');
			@unlink($tempFileName);
			Logger::info('Uploaded media', ['id' => $id]);
			$this->jsonExit(DI::mstdnAttachment()->createFromAttach($id));
		}
	}

	public function put(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_WRITE);
		$uid = self::getCurrentUserID();

		$request = $this->getRequest([
			'file'        => [], // The file to be attached, using multipart form data.
			'thumbnail'   => [], // The custom thumbnail of the media to be attached, using multipart form data.
			'description' => '', // A plain-text description of the media, for accessibility purposes.
			'focus'       => '', // Two floating points (x,y), comma-delimited ranging from -1.0 to 1.0
		], $request);

		if (empty($this->parameters['id'])) {
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
		}

		if (DI::mstdnAttachment()->isAttach($this->parameters['id']) && Attach::exists(['id' => substr($this->parameters['id'], 7)])) {
			$this->jsonExit(DI::mstdnAttachment()->createFromAttach(substr($this->parameters['id'], 7)));
		}

		$photo = Photo::selectFirst(['resource-id'], ['id' => $this->parameters['id'], 'uid' => $uid]);
		if (empty($photo['resource-id'])) {
			$media = Post\Media::getById($this->parameters['id']);
			if (empty($media['uri-id'])) {
				$this->logAndJsonError(404, $this->errorFactory->RecordNotFound());
			}
			if (!Post::exists(['uri-id' => $media['uri-id'], 'uid' => $uid, 'origin' => true])) {
				$this->logAndJsonError(404, $this->errorFactory->RecordNotFound());
			}
			Post\Media::updateById(['description' => $request['description']], $this->parameters['id']);
			$this->jsonExit(DI::mstdnAttachment()->createFromId($this->parameters['id']));
		}

		Photo::update(['desc' => $request['description']], ['resource-id' => $photo['resource-id']]);

		$this->jsonExit(DI::mstdnAttachment()->createFromPhoto($this->parameters['id']));
	}

	/**
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	protected function rawContent(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_READ);
		$uid = self::getCurrentUserID();

		if (empty($this->parameters['id'])) {
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
		}

		$id = $this->parameters['id'];

		if (Photo::exists(['id' => $id, 'uid' => $uid])) {
			$this->jsonExit(DI::mstdnAttachment()->createFromPhoto($id));
		}

		if (DI::mstdnAttachment()->isAttach($id) && Attach::exists(['id' => substr($id, 7)])) {
			$this->jsonExit(DI::mstdnAttachment()->createFromAttach(substr($id, 7)));
		}

		$this->logAndJsonError(404, $this->errorFactory->RecordNotFound());
	}
}
