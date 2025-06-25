# Kirby Crumble

Crumble is a comprehensive cookie consent plugin for Kirby CMS. Unlike other plugins, Crumble provides a robust backend API that properly tracks and logs user consents.

Out of the box, it integrates with the popular [cookieconsent](https://github.com/orestbida/cookieconsent/tree/master) library by [Orest Bida](https://github.com/orestbida). But you can also bring your own frontend solution and just use Crumble's backend infrastructure to handle the consent logging.

Crumble is currently in Beta and free to try while we're actively developing it. Once we hit stable release, it'll require a paid license.

The best approach is always to avoid having a 'cookie banner' altogether. But let's be real – sometimes you need them, be it just for iFrame embeds or other third-party scripts. Crumble provides a solid foundation for cookie consent, though remember that compliance ultimately depends on how you implement it. Crumble is provided 'as-is' with no warranty or guarantees. Read the [license agreement](https://plugins.andkindness.com/license-agreement) for more details.

The included texts and translations are for illustrative purposes only and should not be considered legal guidance.

If you need something more automated with legal guarantees, commercial services like [Cookiebot](https://www.cookiebot.com/) might be a better fit.

## What Crumble offers 

- TODO

## Is Crumble right for you?

Here's what you should know before choosing Crumble:

- **Manual setup required**: Your editors need to understand cookie categories and add them manually – there's no automatic cookie scanning
- **Developer needed**: You'll likely need a developer to integrate scripts properly ([see how](https://cookieconsent.orestbida.com/advanced/manage-scripts.html))
- **iFrames need extra work**: Things like YouTube embeds require additional setup with [iFrame Manager](https://cookieconsent.orestbida.com/advanced/iframemanager-setup.html) or a similar solution
- **Not Google-certified**: If you're running Google ads (AdSense, Ad Manager, AdMob), you'll need a [Google Certified CMP](https://support.google.com/adsense/answer/13554020?hl=en&ref_topic=10924967&sjid=11075614997312658217-NA) instead

## License

Kirby Crumble requires a license for production use. You'll need both a valid Kirby license and a [Crumble license](https://plugins.andkindness.com/crumble/pricing) to run it on a public server.

Copyright 2025 © Tobias Möritz - Love & Kindness GmbH
