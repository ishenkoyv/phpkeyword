<?php
function stemmer_stem_ru($word) 
{
    return Stem::stem_word($word);
}

class Stem
{
    private static $VERSION = "0.02";
    private static $Stem_Caching = 0;
    private static $Stem_Cache = array();
    private static $VOWEL = 'аеиоуыэюя';
    private static $PERFECTIVEGROUND = '((ив|ивши|ившись|ыв|ывши|ывшись)|((?<=[ая])(в|вши|вшись)))$';
    private static $REFLEXIVE = '(с[яь])$';
    private static $ADJECTIVE = '(ее|ие|ые|ое|ими|ыми|ей|ий|ый|ой|ем|им|ым|ом|его|ого|ему|ому|их|ых|ую|юю|ая|яя|ою|ею)$';
    private static $PARTICIPLE = '((ивш|ывш|ующ)|((?<=[ая])(ем|нн|вш|ющ|щ)))$';
    private static $VERB = '((ила|ыла|ена|ейте|уйте|ите|или|ыли|ей|уй|ил|ыл|им|ым|ен|ило|ыло|ено|ят|ует|уют|ит|ыт|ены|ить|ыть|ишь|ую|ю)|((?<=[ая])(ла|на|ете|йте|ли|й|л|ем|н|ло|но|ет|ют|ны|ть|ешь|нно)))$';
    private static $NOUN = '(а|ев|ов|ие|ье|е|иями|ями|ами|еи|ии|и|ией|ей|ой|ий|й|иям|ям|ием|ем|ам|ом|о|у|ах|иях|ях|ы|ь|ию|ью|ю|ия|ья|я)$';
    private static $RVRE = '^(.*?[аеиоуыэюя])(.*)$';
    private static $DERIVATIONAL = '[^аеиоуыэюя][аеиоуыэюя]+[^аеиоуыэюя]+[аеиоуыэюя].*(?<=о)сть?$';

    private static function s(&$s, $re, $to)
    {
        $orig = $s;
        $s = mb_ereg_replace($re, $to, $s);
        return $orig !== $s;
    }

    private static function m($s, $re)
    {
        return mb_ereg_match($re, $s);
    }

    public static function stem_word($word)
    {
		mb_regex_encoding( 'UTF-8' );
		mb_internal_encoding( 'UTF-8' );

        $word = mb_strtolower($word);
        $word = str_replace( 'ё', 'е', $word );
        # Check against cache of stemmed words
        if (self::$Stem_Caching && isset(self::$Stem_Cache[$word])) {
            return self::$Stem_Cache[$word];
        }
        $stem = $word;
        do {
          if (!mb_ereg(self::$RVRE, $word, $p)) break;
          $start = $p[1];
          $RV = $p[2];
          if (!$RV) break;

          # Step 1
          if (!self::s($RV, self::$PERFECTIVEGROUND, '')) {
              self::s($RV, self::$REFLEXIVE, '');

              if (self::s($RV, self::$ADJECTIVE, '')) {
                  self::s($RV, self::$PARTICIPLE, '');
              } else {
                  if (!self::s($RV, self::$VERB, ''))
                      self::s($RV, self::$NOUN, '');
              }
          }

          # Step 2
          self::s($RV, 'и$', '');

          # Step 3
          if (self::m($RV, self::$DERIVATIONAL))
              self::s($RV, 'ость?$', '');

          # Step 4
          if (!self::s($RV, 'ь$', '')) {
              self::s($RV, 'ейше?', '');
              self::s($RV, 'нн$', 'н');
          }

          $stem = $start.$RV;
        } while(false);
        if (self::$Stem_Caching) self::$Stem_Cache[$word] = $stem;
        return $stem;
    }

    private static function stem_caching($parm_ref)
    {
        $caching_level = @$parm_ref['-level'];
        if ($caching_level) {
            if (!self::m($caching_level, '^[012]$')) {
                die(__CLASS__ . "::stem_caching() - Legal values are '0','1' or '2'. '$caching_level' is not a legal value");
            }
            self::$Stem_Caching = $caching_level;
        }
        return self::$Stem_Caching;
    }

    private static function clear_stem_cache()
    {
        self::$Stem_Cache = array();
    }

    /*
     * SOME ADDITIONAL METHODS TO WORK WITH TRANSLIT
     */


    public static function translit($str)
    {
        $transtable = array();
        $transtable = array(
            // Russian cyrillic
            'а'=>'a','А'=>'A','б'=>'b','Б'=>'B','в'=>'v','В'=>'V','г'=>'g','Г'=>'G','д'=>'d','Д'=>'D',
            'е'=>'e','Е'=>'E','ё'=>'jo','Ё'=>'Jo','ж'=>'zh','Ж'=>'Zh','з'=>'z','З'=>'Z','и'=>'i','И'=>'I',
            'й'=>'j','Й'=>'J','к'=>'k','К'=>'K','л'=>'l','Л'=>'L','м'=>'m','М'=>'M','н'=>'n','Н'=>'N',
            'о'=>'o','О'=>'O','п'=>'p','П'=>'P','р'=>'r','Р'=>'R','с'=>'s','С'=>'S','т'=>'t','Т'=>'T',
            'у'=>'u','У'=>'U','ф'=>'f','Ф'=>'F','х'=>'kh','Х'=>'KH','ц'=>'c','Ц'=>'C','ч'=>'ch','Ч'=>'Ch',
            'ш'=>'sh','Ш'=>'Sh','щ'=>'sch','Щ'=>'Sch','ъ'=>'','Ъ'=>'','ы'=>'y','Ы'=>'Y','ь'=>'','Ь'=>'',
            'э'=>'eh','Э'=>'Eh','ю'=>'ju','Ю'=>'Ju','я'=>'ja','Я'=>'Ja',

            // Ukrainian cyrillic
            'Ґ'=>'Gh','ґ'=>'gh','Є'=>'Je','є'=>'je','І'=>'I','і'=>'i','Ї'=>'Ji','ї'=>'ji',);
            
        $str = strtr($str, $transtable);
        return $str;
    }
}
