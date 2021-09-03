<?php


namespace App;


use phpDocumentor\Reflection\Types\Mixed_;

/**
 * Штука для автоматической нумерации пунктов при генерации многоуровневого блочного текста
 * с возможностью сохранения номеров пунктов для использования далее по тексту
 * Class DocNumerator
 * @package App
 */
class DocNumerator
{
    private $position;
    private $startFrom = 1;
    private $anchors = [];
    private $ending = ".&nbsp;";
    private $indent = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
    private $separator = '.';

    public function __construct(string $startValue = null)
    {
        $this->reset($startValue);
        return $this;
    }

    /**
     * Установка своего разделителя уровней нумерации
     *
     * @param string $newSeparator
     */
    public function setSeparator(string $newSeparator): void
    {
        $this->separator = $newSeparator;
    }

    /**
     * Кастомные ведущие символы для возвращаемого номера.
     *
     * @param string $newIndent
     */
    public function setIndent(string $newIndent):void
    {
        $this->indent = $newIndent;
    }

    /**
     * Кастомное окончание возвращаемого номера
     * @param string $newEnding
     */
    public function setEnding(string $newEnding):void
    {
        $this->ending = $newEnding;
    }

    /**
     * Старт счетчика
     * можно указать значение типа "12.1.2"
     * --!!!!Точка в конце добавит ".1" к номеру!!!!--
     *
     * возвращает результирующую строку
     * @param null $startValue
     * @return string
     */
    public function start(string $startValue = null):string
    {
        return $this->reset($startValue);
    }

    /**
     * Линейное увеличение счетчика
     * возвращает результирующую строку
     * @return string
     */
    public function next():string
    {
        return $this->increase();
    }

    /**
     * Добавление уровня, возвращает результирующую строку
     * @return string
     */
    public function push():string
    {
        return $this->addLevel();
    }

    /**
     * Возврат на $count=1 уровней вверх; затем увеличивает счетчик
     * возвращает результирующую строку
     * @param int $count
     * @return string
     */
    public function pop(int $count = 1):string
    {
        return $this->removeLevel($count);
    }

    /**
     * Жесткий переход на нужный уровень, увеличивает счетчик при переходе выше,
     * возвращает результирующую строку
     * @param $levelNumber
     * @return string
     */
    public function to(int $levelNumber):string
    {
        return $this->setLevel($levelNumber);
    }

    /**
     * Возвращает строкой текущую позицию
     * @param bool $noEnding
     * @return string
     */
    public function get(bool $noEnding = false):string
    {
        return $this->getValue($noEnding);
    }

    /**
     * Фиксация номера пункта для дальнейшего ссылания на него в тексте
     * @param $name
     */
    public function setAnchor($name):void
    {
        $this->anchors[$name] = $this->getValue(false);
    }

    /**
     * Получение номера пункта по сохраненному имени
     * @param $name
     * @return mixed|null
     */
    public function getAnchor($name)
    {
        if (isset($this->anchors[$name])){
            return $this->anchors[$name];
        }
        return null;
    }

    /**
     * Возвращает массив анкеров
     * @return array
     */
    public function anchors():array
    {
        return $this->anchors;
    }

    /**
     * Возвращает номер текущего уровня
     * @return int
     */
    public function getLevel():int
    {
        return count($this->position);
    }

    /**
     * Возвращает текущее значение строкой. По умолчанию пихается "красная строка" и окончание
     * @param bool $noEnding    - Не добавлять окончание ($this->ending)
     * @param bool $noIndent    - Не добавлять красную строку в начало ($this->indent)
     * @return string
     */
    protected function getValue($noEnding=false, $noIndent=false):string
    {
        return ($noIndent ? '' : $this->indent)
               . implode($this->separator, $this->position)
               . ($noEnding ? '' : $this->ending);
    }

    /**
     * Установка нового значения в виде строки без закрывающей точки
     * возвращает сформированное текущее значение
     * @param $value
     * @return string
     */
    protected function setValue(string $value):string
    {
        $this->position = array();
        $this->position = explode('.', $value);
        return $this->getValue();
    }

    /**
     * Увеличение счетчика. Возвращает результирующее текстовое значение
     * @return string
     */
    protected function increase():string
    {
        $tempArr = array_reverse($this->position);
        $tempArr[0]++;
        $this->position = array_reverse($tempArr);
        return $this->getValue();
    }

    /**
     * Сбрасывает счетчик в 1 или указанное строкой значнеие
     * @param null $startValue
     * @return string
     */
    protected function reset(string $startValue = null):string
    {
        $this->position = explode('.', is_null($startValue) ? '1' : $startValue);
        foreach ($this->position as &$num){
            if (!is_numeric($num)){
                $num = $this->startFrom;
            }
        }
        return $this->getValue();
    }

    /**
     * Добавляет уровень нумерации
     * @param int $count
     * @return string
     */
    protected function addLevel(int $count = 1):string
    {
        for ($i = 1; $i <= $count; $i++){
            array_push($this->position, $this->startFrom);
        }
        return $this->getValue();
    }

    /**
     * Отбрасывает один уровень, увеличивает значение ставшего текущим,
     * возвращает результирующую строку
     * @param int $count
     * @return string
     */
    protected function removeLevel(int $count = 1):string
    {
        if ($count >= count($this->position)){
            return $this->setLevel(1);
        }
        for ($i = 1; $i <=$count; $i++){
            array_pop($this->position);
        }
        return $this->increase();
    }

    /**
     * Устанавливает необходимый уровень. Если глубже текущего - дополнит стартовыми значениями,
     * Если выше текущего - обрежет лишнее и инкрементирует ставший текущим.
     * @param $level
     * @return string
     */
    protected function setLevel($level):string
    {
        if (!is_numeric($level)){
            return $this->getValue();
        }
        if ($level > $this->getLevel()){
            return $this->addLevel($level - $this->getLevel());
        }
        if ($level < $this->getLevel()){
            return $this->removeLevel($this->getLevel() - $level);
        }
        return $this->increase();
    }

}
