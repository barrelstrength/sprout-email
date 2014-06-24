<?php 
/**
 * This test will create a campaign, verify that everything is ok, and then delete it.
 */

// login
$I = new AcceptanceTester($scenario);
$I->wantTo('create a notification, verify successful creation, and delete');
$I->amOnPage('/admin/login');
$I->fillField('username', 'admin');
$I->fillField('password', 'password');
$I->click('#login-form input[type=submit]');
$I->amOnPage('/admin/dashboard');
$I->see('dashboard');

// go to sprout email
$I->click('Sprout Email');
$I->amOnPage('/admin/sproutemail');

// go to notifications
$I->click('Notifications');
$I->amOnPage('/admin/sproutemail/campaigns');

// create new notification
$I->click('a.btn.submit');
$I->see('new campaign');

// fill out form and submit
$I->fillField('name', 'Codeception Test campaign 1');
$I->fillField('fromName', 'Tester From Name');
$I->fillField('fromEmail', 'tester@test.com');
$I->fillField('replyToEmail', 'tester@test.com');
$I->click('.save-and-continue');

$I->see('email content section');
$I->selectOption('#sectionId', '1');
$I->click('.save-and-continue');

$I->see('add recipients');
$I->fillField('#recipients', 'test@test.com');
$I->checkOption('input[type=checkbox]:nth-child(1)');
$I->checkOption('#emailProviderRecipientListIdUser'); // we wil always have at least the admin user
$I->click('.btn.submit');

// verify
$I->see('codeception test campaign');

// logout
$I->click('.myaccount');
$I->click('Sign out');