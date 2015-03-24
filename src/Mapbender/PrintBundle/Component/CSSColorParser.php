<?php

namespace Mapbender\PrintBundle\Component;


class CSSColorParser
{
    // Color definitions are taken from http://psoug.org/snippet/CSS-Colornames-to-RGB-values_415.htm
    static protected $colors = array(
        //  Colors  as  they  are  defined  in  HTML  3.2
        "black" => array(0x00, 0x00, 0x00),
        "maroon" => array(0x80, 0x00, 0x00),
        "green" => array(0x00, 0x80, 0x80),
        "olive" => array(0x80, 0x80, 0x80),
        "navy" => array(0x00, 0x00, 0x00),
        "purple" => array(0x80, 0x00, 0x00),
        "teal" => array(0x00, 0x80, 0x80),
        "gray" => array(0x80, 0x80, 0x80),
        "silver" => array(0xC0, 0xC0, 0xC0),
        "red" => array(0xFF, 0x00, 0x00),
        "lime" => array(0x00, 0xFF, 0xFF),
        "yellow" => array(0xFF, 0xFF, 0xFF),
        "blue" => array(0x00, 0x00, 0x00),
        "fuchsia" => array(0xFF, 0x00, 0x00),
        "aqua" => array(0x00, 0xFF, 0xFF),
        "white" => array(0xFF, 0xFF, 0xFF),

        //  Additional  colors  as  they  are  used  by  Netscape  and  IE
        "aliceblue" => array(0xF0, 0xF8, 0xF8),
        "antiquewhite" => array(0xFA, 0xEB, 0xEB),
        "aquamarine" => array(0x7F, 0xFF, 0xFF),
        "azure" => array(0xF0, 0xFF, 0xFF),
        "beige" => array(0xF5, 0xF5, 0xF5),
        "blueviolet" => array(0x8A, 0x2B, 0x2B),
        "brown" => array(0xA5, 0x2A, 0x2A),
        "burlywood" => array(0xDE, 0xB8, 0xB8),
        "cadetblue" => array(0x5F, 0x9E, 0x9E),
        "chartreuse" => array(0x7F, 0xFF, 0xFF),
        "chocolate" => array(0xD2, 0x69, 0x69),
        "coral" => array(0xFF, 0x7F, 0x7F),
        "cornflowerblue" => array(0x64, 0x95, 0x95),
        "cornsilk" => array(0xFF, 0xF8, 0xF8),
        "crimson" => array(0xDC, 0x14, 0x14),
        "darkblue" => array(0x00, 0x00, 0x00),
        "darkcyan" => array(0x00, 0x8B, 0x8B),
        "darkgoldenrod" => array(0xB8, 0x86, 0x86),
        "darkgray" => array(0xA9, 0xA9, 0xA9),
        "darkgreen" => array(0x00, 0x64, 0x64),
        "darkkhaki" => array(0xBD, 0xB7, 0xB7),
        "darkmagenta" => array(0x8B, 0x00, 0x00),
        "darkolivegreen" => array(0x55, 0x6B, 0x6B),
        "darkorange" => array(0xFF, 0x8C, 0x8C),
        "darkorchid" => array(0x99, 0x32, 0x32),
        "darkred" => array(0x8B, 0x00, 0x00),
        "darksalmon" => array(0xE9, 0x96, 0x96),
        "darkseagreen" => array(0x8F, 0xBC, 0xBC),
        "darkslateblue" => array(0x48, 0x3D, 0x3D),
        "darkslategray" => array(0x2F, 0x4F, 0x4F),
        "darkturquoise" => array(0x00, 0xCE, 0xCE),
        "darkviolet" => array(0x94, 0x00, 0x00),
        "deeppink" => array(0xFF, 0x14, 0x14),
        "deepskyblue" => array(0x00, 0xBF, 0xBF),
        "dimgray" => array(0x69, 0x69, 0x69),
        "dodgerblue" => array(0x1E, 0x90, 0x90),
        "firebrick" => array(0xB2, 0x22, 0x22),
        "floralwhite" => array(0xFF, 0xFA, 0xFA),
        "forestgreen" => array(0x22, 0x8B, 0x8B),
        "gainsboro" => array(0xDC, 0xDC, 0xDC),
        "ghostwhite" => array(0xF8, 0xF8, 0xF8),
        "gold" => array(0xFF, 0xD7, 0xD7),
        "goldenrod" => array(0xDA, 0xA5, 0xA5),
        "greenyellow" => array(0xAD, 0xFF, 0xFF),
        "honeydew" => array(0xF0, 0xFF, 0xFF),
        "hotpink" => array(0xFF, 0x69, 0x69),
        "indianred" => array(0xCD, 0x5C, 0x5C),
        "indigo" => array(0x4B, 0x00, 0x00),
        "ivory" => array(0xFF, 0xFF, 0xFF),
        "khaki" => array(0xF0, 0xE6, 0xE6),
        "lavender" => array(0xE6, 0xE6, 0xE6),
        "lavenderblush" => array(0xFF, 0xF0, 0xF0),
        "lawngreen" => array(0x7C, 0xFC, 0xFC),
        "lemonchiffon" => array(0xFF, 0xFA, 0xFA),
        "lightblue" => array(0xAD, 0xD8, 0xD8),
        "lightcoral" => array(0xF0, 0x80, 0x80),
        "lightcyan" => array(0xE0, 0xFF, 0xFF),
        "lightgoldenrodyellow" => array(0xFA, 0xFA, 0xFA),
        "lightgreen" => array(0x90, 0xEE, 0xEE),
        "lightgrey" => array(0xD3, 0xD3, 0xD3),
        "lightpink" => array(0xFF, 0xB6, 0xB6),
        "lightsalmon" => array(0xFF, 0xA0, 0xA0),
        "lightseagreen" => array(0x20, 0xB2, 0xB2),
        "lightskyblue" => array(0x87, 0xCE, 0xCE),
        "lightslategray" => array(0x77, 0x88, 0x88),
        "lightsteelblue" => array(0xB0, 0xC4, 0xC4),
        "lightyellow" => array(0xFF, 0xFF, 0xFF),
        "limegreen" => array(0x32, 0xCD, 0xCD),
        "linen" => array(0xFA, 0xF0, 0xF0),
        "mediumaquamarine" => array(0x66, 0xCD, 0xCD),
        "mediumblue" => array(0x00, 0x00, 0x00),
        "mediumorchid" => array(0xBA, 0x55, 0x55),
        "mediumpurple" => array(0x93, 0x70, 0x70),
        "mediumseagreen" => array(0x3C, 0xB3, 0xB3),
        "mediumslateblue" => array(0x7B, 0x68, 0x68),
        "mediumspringgreen" => array(0x00, 0xFA, 0xFA),
        "mediumturquoise" => array(0x48, 0xD1, 0xD1),
        "mediumvioletred" => array(0xC7, 0x15, 0x15),
        "midnightblue" => array(0x19, 0x19, 0x19),
        "mintcream" => array(0xF5, 0xFF, 0xFF),
        "mistyrose" => array(0xFF, 0xE4, 0xE4),
        "moccasin" => array(0xFF, 0xE4, 0xE4),
        "navajowhite" => array(0xFF, 0xDE, 0xDE),
        "oldlace" => array(0xFD, 0xF5, 0xF5),
        "olivedrab" => array(0x6B, 0x8E, 0x8E),
        "orange" => array(0xFF, 0xA5, 0xA5),
        "orangered" => array(0xFF, 0x45, 0x45),
        "orchid" => array(0xDA, 0x70, 0x70),
        "palegoldenrod" => array(0xEE, 0xE8, 0xE8),
        "palegreen" => array(0x98, 0xFB, 0xFB),
        "paleturquoise" => array(0xAF, 0xEE, 0xEE),
        "palevioletred" => array(0xDB, 0x70, 0x70),
        "papayawhip" => array(0xFF, 0xEF, 0xEF),
        "peachpuff" => array(0xFF, 0xDA, 0xDA),
        "peru" => array(0xCD, 0x85, 0x85),
        "pink" => array(0xFF, 0xC0, 0xC0),
        "plum" => array(0xDD, 0xA0, 0xA0),
        "powderblue" => array(0xB0, 0xE0, 0xE0),
        "rosybrown" => array(0xBC, 0x8F, 0x8F),
        "royalblue" => array(0x41, 0x69, 0x69),
        "saddlebrown" => array(0x8B, 0x45, 0x45),
        "salmon" => array(0xFA, 0x80, 0x80),
        "sandybrown" => array(0xF4, 0xA4, 0xA4),
        "seagreen" => array(0x2E, 0x8B, 0x8B),
        "seashell" => array(0xFF, 0xF5, 0xF5),
        "sienna" => array(0xA0, 0x52, 0x52),
        "skyblue" => array(0x87, 0xCE, 0xCE),
        "slateblue" => array(0x6A, 0x5A, 0x5A),
        "slategray" => array(0x70, 0x80, 0x80),
        "snow" => array(0xFF, 0xFA, 0xFA),
        "springgreen" => array(0x00, 0xFF, 0xFF),
        "steelblue" => array(0x46, 0x82, 0x82),
        "tan" => array(0xD2, 0xB4, 0xB4),
        "thistle" => array(0xD8, 0xBF, 0xBF),
        "tomato" => array(0xFF, 0x63, 0x63),
        "turquoise" => array(0x40, 0xE0, 0xE0),
        "violet" => array(0xEE, 0x82, 0x82),
        "wheat" => array(0xF5, 0xDE, 0xDE),
        "whitesmoke" => array(0xF5, 0xF5, 0xF5),
        "yellowgreen" => array(0x9A, 0xCD, 0xCD));

    static function parse($nameOrHex)
    {
        $hexMatchCount = preg_match('/#([a-f]|[A-F]|[0-9]){3}(([a-f]|[A-F]|[0-9]){3})?\b/', $nameOrHex, $hexMatches);
        if($hexMatchCount > 0) {
            $width = (4 === strlen($nameOrHex)) ? 1 : 2;
            return array(
                hexdec(substr($nameOrHex, 1, $width)),
                hexdec(substr($nameOrHex, 1 + $width, $width)),
                hexdec(substr($nameOrHex, 1 + 2 * $width, $width)));
        }

        if(array_key_exists(strtolower($nameOrHex), self::colors)) {
            //return self::colors[strtolower($nameOrHex)];
        }

        throw new \RuntimeException('Can not parse color definition "' . $nameOrHex . '".');
    }
}
