<?php    
    chdir(dirname(__FILE__));   
    ini_set("max_execution_time", "12000000");  // увеличиваем время
    ini_set('memory_limit', '1024M');  // увеличиваем память
   
/**
 *  Класс для парсинга слова
 */
class Mutate
{
    public $result = null; // вершина-результат
    public $source = "ваня"; // начальное слово. Приходит из POST переменной.
    public $destination = "маша"; // конеченое слово. Приходит из POST переменной.    
    private $alphabet = array('а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п','р','с','т','у','ф','х','ц','ч','ш','щ','ъ','ы','ь','э','ю','я');
    private $peaks = null; // массив вершин. Ключи массива пути до вершин, значения - текущая вершина(слово). 
    private $dict; // словарь

    /**
     * Конструктор
     * Собирает словарь из БД
     * 
     * @param type $config конфиг БД
     */
    public function __construct()
    {
        $dictArray = file("dict-utf8.txt", FILE_IGNORE_NEW_LINES);
        foreach ($dictArray as $key => $value) {
            $this->dict[$value] = 1;
        }
    }   
    
    /**
     *  Функция-итератор до момента пока не найдется слово.
     * 
     *  @access public
     */
    public function calculate()
    {
        $this->peaks[$this->source] = $this->source;
        unset($this->dict[$this->source]);
        $isNotFound = true;
        while ($isNotFound) 
        {
            $isNotFound = $this->checkPeaks();
            if ($this->peaks == null) {
                break;
            }
        }
    }
    
    /**
     * Если не выполнено условие найденного конечного слова,то
     * в цикле находит потомков текущего массива вершин и собирает новый массив вершин графа.
     * 
     * @return boolean true - не найдено условие, false - найдено 
     */
    protected function checkPeaks() 
    {
        foreach ($this->peaks as $peakkey => $peakvalue) { 
            $piece_of_peaks = $this->findChilds($peakvalue);            
            if ($piece_of_peaks === false) {
                $this->result = $peakkey;
                return false;
            }
            $this->packPeaks($piece_of_peaks, $peakkey);            
        }        
        return true;
    }

    /**
     * Создает новую ветку узлов на основе старой ветки. Ключ ветки узлов это путь до вершины, значение - вершина. 
     * 
     * @param type $piece_of_peaks
     * @param type $key
     */
    protected function packPeaks($piece_of_peaks, $key)
    {
        unset($this->peaks[$key]);
        foreach ($piece_of_peaks as $p) {
            $this->peaks[$key . '_' . $p] = $p;             
        }
    }

    /**
     *  Для каждой вершины находит слова-потомки из словаря.
     * 
     * @param type $source вершина графа вида "слово1_слово2_слово3"
     * @return массив мутации слова
     */
    protected function findChilds($source)
    {
        $piece_of_peaks = array(); // заполняемый массив потомков текущего слова
        $arSource = preg_split('//u', $source, -1, PREG_SPLIT_NO_EMPTY); // разбираем текущее слово в массив
        $iteration = $arSource;
        foreach ($iteration as $sourceСhark => $v) { // смотрим каждый символ текущего слова  в отдельности
            $iteration = $arSource;
            foreach ($this->alphabet as $alpChar) {  // перебираем алфивит для текущего символа
                $iteration[$sourceСhark] = $alpChar;
                $mutate_word = implode('', $iteration);
                if (isset($this->dict[$mutate_word])) {                 
                    if ($mutate_word == $this->destination) {                        
                        return false;  // успешное условие найденного конечного слова
                    }
                    $piece_of_peaks[] = $mutate_word;                 
                    unset($this->dict[$mutate_word]); // удаляем пройденную вершину из словаря
                }
                unset($mutate_word);
            }            
        } 
        return $piece_of_peaks;
    }            
}

$m = new Mutate();
$m->source = $argv[1];
$m->destination = $argv[2];
$m->calculate();
if ($m->result === null) {
    echo 'результатов не найдено' . "\n";
} else {
    $result = explode('_', $m->result);
    foreach ($result as $key => $word) {
        if ($key == 0) {
            echo $word;
        } else {
            echo ' -> ' . $word;
        }
    }
    echo ' -> ' . $m->destination . "\n";
}



