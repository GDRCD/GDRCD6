# GDRCD 6

GDRCD nasce come CMS per lo sviluppo di giochi di ruolo "Play By Chat".

__Fai Attenzione__: questa versione è ancora in fase di sviluppo, se stai cercando un CMS per creare il tuo 
gioco di ruolo su chat, forse stavi cercando [GDRCD 5.2](https://github.com/GDRCD/GDRCD)


## Filosofia

Attualmente chi sviluppa il codice è un gruppo di appassionati che hanno voglia di migliorarsi e al contempo 
di proporre qualcosa di buono e al passo con i tempi.  
Chiunque volesse aiutare è il benvenuto e può proporre idee o rendersi parte attiva nello sviluppo di 
migliorie e implementazioni, l'importante è seguire le linee guida definite per il progetto assicurandosi di 
prendere visione delle milestones e di quanto scritto in questo documento.


## Regole di scrittura del codice

Un software ordinato richiede uno sviluppo ordinato e a tal proposito abbiamo stilato delle regole di 
scrittura del codice che si rifanno vagamente a quelle del PHP FIG <http://www.php-fig.org/faq/>.

### Codifica del File

Sempre e obbligatoriamente i file prodotti devono essere in codifica UTF-8

### Tabulazione

Niente tab, si indenta con 4 spazi

### Margine di stampa

Non più di 110 caratteri su una singola riga, è meglio prendere i 100 come riferimento medio

### Commenti

Ci si rifà alla sintassi JavaDoc, nei seguenti modi:

#### Commento di inizio file o per una classe

    /**
        * All'inizio di ogni file si crea un commento come questo in cui si spiega brevemente a cosa quel
        * file o quella classe serva e a definirne il @package.
        * Il parametro @package va definito come fosse un namespace.
        *
        * @package \Path\To\Folder
    */

#### Commento su funzioni/metodi
 
    /**
        * Bisogna spiegare in maniera dettagliata a cosa la funzione serve, quali parametri accetta
        * e se abbisogna di valori particolari (e in caso indicare quali).
        * Bisogna definire inoltre @param per indicare i parametri, @return per indicare il tipo di dato che
        * la funzione ritorna e @throws per indicare il nome della classe di eccezione sollevata (Exception)
        * o la classe d'errore generata (E_NOTICE, E_WARNING, E_FATAL, E_DEPRECATED)
        *
        * @param (string) $parametro1 <note aggiuntive>
        * @param (int) $parametro2 <note aggiuntive>
        *
        * @throws Exception
        * @return bool
    */

#### Commento generico

    /**
        * Commento generico che può esser posto per spiegare il funzionamento di variabili o altro
    */

#### Micro commento

    #> Commento non rilevato dalla doc, da usare solo per piccole indicazioni nel file


### Parentesi graffe

#### Classi e funzioni

Per classi e/o funzioni sia la parentesi di apertura che quella di chiusura vanno sempre su un nuovo rigo
 
    class MyClass
    {
        #> Some Code..
    }

#### Strutture di controllo

Per le strutture di controllo di linguaggio la parentesi di apertura rimane sul rigo corrente mentre quella di chiusura va su un nuovo rigo

    if ($something) {
        #> Some Code..
    }


### Nomi funzioni, classi e metodi

I nomi delle funzioni e dei metodi di una classe vanno definiti in `camelCase`

    function myFunction()
    {
        #> Some Code..
    }

I nomi delle classi vanno definiti in `StudlyCaps`

    class MyUberClass
    {
        #> Some Code..
    }


### Espressioni molto lunge

Può capitare che un espressione molto lunga superi i margini di stampa definiti, con le regole precedenti si 
può evitare questo quando si parla di funzioni, classi o costrutti logici di linguaggio ma potrebbe capitare 
che una funzione abbia molti metodi o per un qualche motivo ci ritroviamo a popolare un array con una decina 
di valori, in questo caso va adottata una logica simile a quella applicata sulle if: il primo "braccio"
(che sia una parentesi graffa o tonda) rimane sullo stesso rigo e il resto va a capo.  
Bisogna andare a capo per ogni parametro presenti la funzione o ogni qual volta si usa un unificatore di 
stringa:  
nel primo caso la virgola va dopo il dato, nel secondo il punto va sempre prima.  
Prendo due esempi pratici scritti nell'attuale sorgente:

    $this->DBObj = new PDO(
        "mysql:host={$host};dbname={$database};charset=utf8", 
        $user, 
        $pass,
        array(
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        )
    );

    $className = 
        dirname(dirname(__FILE__))
        . GDRCD_DS
        . 'application'
        . GDRCD_DS
        . $this->currentApplication()
        . GDRCD_DS
        . 'controller'
        . GDRCD_DS
        . $className
        . '.class.php';
    
La vera regola d'oro è sempre quella di rispettare l'indentazione.


## Documentazione, supporto, updates

Essendo una versione ancora in sviluppo __non__ c'è alcun supporto ad essa.  
Per la documentazione si può fare riferimento a questo file e alla wiki per adesso.  
Per aggiornamenti basta non perdere di vista @GDRCD su GitHub ;-)


## Licenza Software (MIT)

> Copyright (c) 2013 GDRCD
> 
> Permission is hereby granted, free of charge, to any person
> obtaining a copy of this software and associated documentation
> files (the "Software"), to deal in the Software without
> restriction, including without limitation the rights to use,
> copy, modify, merge, publish, distribute, sublicense, and/or sell
> copies of the Software, and to permit persons to whom the
> Software is furnished to do so, subject to the following
> conditions:
> 
> The above copyright notice and this permission notice shall be
> included in all copies or substantial portions of the Software.

> THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
> EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
> OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
> NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
> HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
> WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
> FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
> OTHER DEALINGS IN THE SOFTWARE.