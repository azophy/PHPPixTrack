PHPPixTracker
=============

Slightly better PHP based pixel tracker for email

Services such as bananatag.com or gmail tracker use 1x1 pixel which embedded to
emails as tracker, which anytime accessed would log as read action.

## Features Target:
- simple pixel-based email tracker to be used for Libre Office mail-merge mass sending capabilities
- prefered deployment method:
    - single php file. output as csv or postgresql database (by setting env variable)
    - as heroku / digital ocean instence with 1 click install
- optional tag for detailed analytics targetting
- basic plain dasboard with current analytic log result & image url display to be copied
- optional pretty dsashboard for better visualization

## analytic data idea
- read status : time, ip, browser agent
- link click status : time, ip, browser agent
- notification to email / slack / other webhook

## inspiration
- https://github.com/brampauwelyn/php-email-tracker
- https://github.com/johnathanmiller/PHP-Pixel-Tracker

- https://github.com/Kickball/awesome-selfhosted#analytics
    - https://suet.co/
    - https://github.com/damianofalcioni/IP-Biter
    - https://github.com/sirodoht/sqone

## Great references
    - https://stackoverflow.com/questions/13079666/developing-a-tracking-pixel



