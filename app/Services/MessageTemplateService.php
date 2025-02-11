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
                'email_content' => "Hi,\n\nIt's been 24 hours since your free trial ended. We noticed you haven't subscribed yet—why wait?\n\n🔥 Get instant access to premium channels, top movies, and exclusive sports.\n🔥 No buffering, no limits—just the best IPTV experience!\n\nRejoin now and don't miss out: " . self::getPageLink() . "\n\nBest,\n" . self::getPageName()
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
