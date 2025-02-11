<?php

namespace App\Services;

class MessageTemplateService
{
    private static function getPageLink(): string
    {
        return config('services.facebook.page_link');
    }

    private static function getPageName(): string
    {
        return config('services.facebook.page_name');
    }

    public static function getTrialTemplate(string $type, string $medium): string
    {
        $templates = [
            'first' => [
                'facebook' => "🚨 Your free trial is almost up! 🚨\nYou have 3 hours left to enjoy our premium IPTV service. Don't miss out on unlimited entertainment—upgrade now and keep watching hassle-free!",
                'email_subject' => "⚠️ Your Free Trial Ends Soon – Act Now!",
                'email_content' => "Hi,\n\nYour free IPTV trial is expiring in just 3 hours! Don't let the fun stop—upgrade now and continue enjoying high-quality entertainment with the best channels and movies.\n\n🔹 Unlimited HD streaming\n🔹 No buffering, just seamless entertainment\n🔹 24/7 customer support\n\ncontact us here to subscribe now and avoid interruptions: " . self::getPageLink() . "\n\nBest,\n" . self::getPageName()
            ],
            'second' => [
                'facebook' => "⏳ Your trial has just expired! Don't lose access to the best IPTV experience. Subscribe now and keep enjoying your favorite channels without interruptions!",
                'email_subject' => "Your Trial Has Ended – But the Fun Doesn't Have To!",
                'email_content' => "Hi,\n\nYour free IPTV trial has just ended! But don't worry—you can still enjoy uninterrupted access to premium content by subscribing now.\n\n🔹 Access 40.000 of channels\n🔹 Exclusive sports, movies & shows\n🔹 Ultra-HD streaming\n\nGet back to your entertainment now: " . self::getPageLink() . "\n\nSee you inside,\n" . self::getPageName()
            ],
            'third' => [
                'facebook' => "🎬 Still missing out on your favorite shows? Your trial ended, but it's not too late! Get full access now with a premium subscription.",
                'email_subject' => "We Miss You! Get Back to the Best IPTV Experience",
                'email_content' => "Hi,\n\nIt's been 24 hours since your free trial ended. We noticed you haven't subscribed yet—why wait?\n\n🔥 Get instant access to premium channels, top movies, and exclusive sports.\n🔥 No buffering, no limits—just the best IPTV experience!\n\nRejoin now: " . self::getPageLink() . "\n\nBest,\n" . self::getPageName()
            ],
            'first_no_trial' => [
                'facebook' => "🌟 Ready to experience premium IPTV? Start your journey with us and unlock a world of entertainment!",
                'email_subject' => "Discover Premium IPTV Entertainment",
                'email_content' => "Hi,\n\nReady to experience the best in IPTV entertainment? Get started now and enjoy:\n\n🔹 40,000+ channels\n🔹 Ultra-HD streaming\n🔹 24/7 support\n\nStart your premium experience: " . self::getPageLink() . "\n\nBest,\n" . self::getPageName()
            ],
            'second_no_trial' => [
                'facebook' => "📺 Don't miss out on premium entertainment! Join thousands of satisfied customers enjoying the best IPTV service.",
                'email_subject' => "Premium Entertainment Awaits You",
                'email_content' => "Hi,\n\nStill thinking about premium IPTV? Here's what you're missing:\n\n🔹 Live sports & events\n🔹 Premium movies & shows\n🔹 Crystal-clear streaming\n\nJoin now: " . self::getPageLink() . "\n\nBest,\n" . self::getPageName()
            ],
            'third_no_trial' => [
                'facebook' => "🎯 Special offer! Get premium IPTV access now and enjoy unlimited entertainment.",
                'email_subject' => "Special IPTV Offer Just for You",
                'email_content' => "Hi,\n\nWe've got a special offer just for you! Join now and get:\n\n🔹 Premium channel access\n🔹 HD & 4K quality\n🔹 Instant activation\n\nClaim your offer: " . self::getPageLink() . "\n\nBest,\n" . self::getPageName()
            ],
            'fourth_no_trial' => [
                'facebook' => "🌈 Transform your TV experience with premium IPTV! Join now for unlimited entertainment.",
                'email_subject' => "Upgrade Your Entertainment Today",
                'email_content' => "Hi,\n\nYour premium entertainment journey awaits!\n\n🔹 Vast content library\n🔹 Premium sports channels\n🔹 No commitments\n\nStart watching: " . self::getPageLink() . "\n\nBest,\n" . self::getPageName()
            ],
            'fifth_no_trial' => [
                'facebook' => "🎁 Last chance! Don't miss out on our premium IPTV service. Subscribe now!",
                'email_subject' => "Last Chance for Premium IPTV",
                'email_content' => "Hi,\n\nThis is your last chance to join our premium IPTV service!\n\n🔹 Unbeatable quality\n🔹 Massive content library\n🔹 24/7 support\n\nJoin now: " . self::getPageLink() . "\n\nBest,\n" . self::getPageName()
            ]
        ];

        return $templates[$type][$medium] ?? "Template not found";
    }

    public static function getMarketingTemplate(int $messageNumber): string
    {
        $templates = [
            1 => "🎉 Why settle for cable when you can have IPTV at its best? Get access to premium channels, sports, and movies for a fraction of the price. 🚀 Try it now: " . self::getPageLink(),
            2 => "⚽ Love live sports? Never miss a game with our IPTV service! Watch matches in HD with zero buffering. Sign up today and stream like a pro! " . self::getPageLink(),
            3 => "📺 Binge-worthy content awaits! With our IPTV, you get thousands of channels, blockbuster movies, and exclusive shows—all in ultra-HD. Don't wait, subscribe now: 20% off " . self::getPageLink(),
            4 => "🚀 Fast, stable, and loaded with entertainment! Our IPTV service gives you unlimited access to premium TV at unbeatable prices. Sign up today and see why customers love us! ❤️ " . self::getPageLink(),
            5 => "💡 Smart viewers choose our IPTV! More content, better quality, and top-tier customer service. Ready for the ultimate upgrade? Subscribe now and start streaming today! " . self::getPageLink()
        ];

        return $templates[$messageNumber] ?? "Template not found";
    }

    public static function getPaidTemplate(string $type, string $medium): string
    {
        $templates = [
            'first' => [
                'facebook' => "⏳ Your IPTV subscription expires in 7 days! Don't wait until the last minute—renew now and enjoy uninterrupted entertainment. 📺 Renew NOW: " . self::getPageLink(),
                'email_subject' => "Your Subscription Expires in 7 Days – Renew Now!",
                'email_content' => "Hi,\n\nYour IPTV subscription is expiring in 7 days! To keep enjoying your favorite channels, sports, and movies without interruption, make sure to renew now.\n\n✔️ Ultra-HD Streaming\n✔️ 24/7 Support\n✔️ 40.000 of Channels\n\nDon't risk losing access—renew today: " . self::getPageLink() . "\n\nBest,\n" . self::getPageName()
            ],
            'second' => [
                'facebook' => "⚠️ Only 2 days left on your IPTV subscription! Don't let your access expire—renew today and continue enjoying unlimited streaming hassle-free. 🔄 " . self::getPageLink(),
                'email_subject' => "🚨 Your IPTV Subscription Ends in 2 Days – Renew Today!",
                'email_content' => "Hi,\n\nJust a reminder—your IPTV subscription expires in 2 days! Avoid any interruptions and renew your plan now to keep watching all your favorite content.\n\n🎬 Stay connected to the best movies, shows & sports!\n\nRenew before it's too late: " . self::getPageLink() . "\n\nBest,\n" . self::getPageName()
            ],
            'third' => [
                'facebook' => "⛔ Your IPTV subscription has expired! We hate to see you go, but you can reactivate your account instantly and get back to watching. Renew Now: " . self::getPageLink(),
                'email_subject' => "Your Subscription Has Expired – Reactivate Now!",
                'email_content' => "Hi,\n\nYour IPTV subscription has expired! But don't worry—you can renew now and instantly restore your access to premium entertainment.\n\n💡 No contracts, just non-stop entertainment. Reactivate now and enjoy the best IPTV experience.\n\nRenew your subscription here: " . self::getPageLink() . "\n\nSee you inside,\n" . self::getPageName()
            ]
        ];

        return $templates[$type][$medium] ?? "Template not found";
    }
}
