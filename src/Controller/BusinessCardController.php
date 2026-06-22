<?php

namespace App\Controller;

use App\Content\ContentProvider;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Common\Version;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BusinessCardController extends AbstractController
{
    #[Route('/card.vcf', name: 'vcard', methods: ['GET'])]
    public function vcard(ContentProvider $contentProvider): Response
    {
        return new Response($this->getVCard($contentProvider, true), 200, ['Content-Type' => 'text/vcard']);
    }

    #[Route('/card', name: 'qrcard', methods: ['GET'])]
    public function qrcard(ContentProvider $contentProvider): Response
    {

        $options = new QROptions();
        $options->version = Version::AUTO;
        $options->versionMin = 7;
        $options->eccLevel = EccLevel::H;
        $options->outputInterface = QRGdImagePNG::class;

        $qrcode = new QRCode($options);

        return $this->render('card.html.twig', [
            'content' => $contentProvider,
            'qrcode' => $qrcode->render($this->getVCard($contentProvider)),
        ]);
    }

    private function getVCard(ContentProvider $contentProvider, bool $withPhoto = false): string
    {
        $vcard = [
            'BEGIN:VCARD',
        ];

        $profile = $contentProvider->getProfile();
        $name = $profile['name'];
        $firstName = explode(' ', $name)[0];
        $lastName = explode(' ', $name)[1];
        $vcard += [
            'VERSION:4.0',
            'FN:' . $name,
            'N:'.$lastName.';'.$firstName.';;;',
            'GENDER:M',
            'EMAIL;TYPE=work:' . $profile['email'],
            'TEL;TYPE=cell,voice:' . $profile['phone'],
            'URL;TYPE=website:https://jeremielibeau.fr/',
            'URL;TYPE=linkedin:' . $profile['linkedin'],
            'TITLE:' . $profile['title'],
            'ORG:' . $profile['company'],
        ];
        if ($withPhoto) {
            $vcard[] = 'PHOTO;ENCODING=b:'.base64_encode(file_get_contents(__DIR__.'/../../public/images/profile.jpg'));
        }

        $vcard[] = 'END:VCARD';

        return implode("\r\n", $vcard);
    }
}
