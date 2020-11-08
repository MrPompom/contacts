<?php
/**
 * @copyright Copyright (c) 2020 Matthias Heinisch <nextcloud@matthiasheinisch.de>
 *
 * @author Matthias Heinisch <nextcloud@matthiasheinisch.de>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OCA\Contacts\Service\Social;

use OCP\Http\Client\IClient;
use OCP\Http\Client\IResponse;
use OCP\Http\Client\IClientService;
use ChristophWurst\Nextcloud\Testing\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class FacebookProviderTest extends TestCase {
	private $provider;

	/** @var IClientService|MockObject */
	private $clientService;

	/** @var IClient|MockObject */
	private $client;

	/** @var IResponse|MockObject */
	private $response;

	protected function setUp(): void {
		parent::setUp();
		$this->clientService = $this->createMock(IClientService::class);
		$this->response = $this->createMock(IResponse::class);
		$this->client = $this->createMock(IClient::class);

		$this->clientService
			->method('NewClient')
			->willReturn($this->client);

		$this->provider = new FacebookProvider(
			$this->clientService
		);
	}

	public function dataProviderSupportsContact() {
		$contactWithSocial = [
			'X-SOCIALPROFILE' => [
				["value" => "username1", "type" => "facebook"],
				["value" => "username2", "type" => "facebook"]
			]
		];

		$contactWithoutSocial = [
			'X-SOCIALPROFILE' => [
				["value" => "one", "type" => "social1"],
				["value" => "two", "type" => "social2"]
			]
		];

		return [
			'contact with facebook fields' => [$contactWithSocial, true],
			'contact without facebook fields' => [$contactWithoutSocial, false]
		];
	}

	/**
	 * @dataProvider dataProviderSupportsContact
	 */
	public function testSupportsContact($contact, $expected) {
		$result = $this->provider->supportsContact($contact);
		$this->assertEquals($expected, $result);
	}

	public function dataProviderGetImageUrls() {
		$contactWithSocial = [
			'X-SOCIALPROFILE' => [
				["value" => "username1", "type" => "facebook"],
				["value" => "username2", "type" => "facebook"]
			]
		];
		$contactWithSocialUrls = [
			"https://www.facebook.com/username1",
			"https://www.facebook.com/username2"
		];
		$contactWithSocialHtml = array_map(function ($profile) {
			return '<meta property="og:image" content="https://'.$profile['value'].'.jpg?_nc_cat=1&amp;ccb=2&amp;_nc_sid=3&amp;_nc_ohc=a_b&amp;oe=123" />';
		}, $contactWithSocial['X-SOCIALPROFILE']);
		$contactWithSocialImg = array_map(function ($profile) {
			return 'https://'.$profile['value'].'.jpg?_nc_cat=1&ccb=2&_nc_sid=3&_nc_ohc=a_b&oe=123';
		}, $contactWithSocial['X-SOCIALPROFILE']);

		$contactWithoutSocial = [
			'X-SOCIALPROFILE' => [
				["value" => "one", "type" => "social1"],
				["value" => "two", "type" => "social2"]
			]
		];
		$contactWithoutSocialUrls = [];
		$contactWithoutSocialHtml = [];
		$contactWithoutSocialImg = [];

		return [
			'contact with facebook fields' => [
				$contactWithSocial,
				$contactWithSocialUrls,
				$contactWithSocialHtml,
				$contactWithSocialImg
			],
			'contact without facebook fields' => [
				$contactWithoutSocial,
				$contactWithoutSocialUrls,
				$contactWithoutSocialHtml,
				$contactWithoutSocialImg
			]
		];
	}

	/**
	 * @dataProvider dataProviderGetImageUrls
	 */
	public function testGetImageUrls($contact, $urls, $htmls, $imgs) {
		if (count($urls)) {
			$this->response
		  ->method('getBody')
		->willReturnOnConsecutiveCalls(...$htmls);

			$urlArgs = array_map(function ($url) {
				return [$url];
			}, $urls);

			$this->client
		->expects($this->exactly(count($urls)))
			->method('get')
		->withConsecutive(...$urlArgs)
		->willReturn($this->response);
		}

		$result = $this->provider->getImageUrls($contact);
		$this->assertEquals($imgs, $result);
	}
}
