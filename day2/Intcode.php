<?php

class Intcode
{
    protected $list = [99];

    public function setList(array $list)
    {
        $this->list = $list;
    }

    /**
     * @throws Exception
     */
    public function run()
    {
        for ($i = 0; $i < count($this->list); $i += 4) {
            $opCode = $this->getValueAt($i);
            $index1 = $this->getValueAt($i + 1);
            $index2 = $this->getValueAt($i + 2);
            switch ($opCode) {
                case 1:
                    $result = $this->getValueAt($index1) + $this->getValueAt($index2);
                    break;
                case 2:
                    $result = $this->getValueAt($index1) * $this->getValueAt($index2);
                    break;
                case 99:
                    return;
                default:
                    throw new Exception('Invalid opcode given');
            }
            $resultIndex = $this->getValueAt($i + 3);
            $this->list[$resultIndex] = $result;
        }
    }

    /**
     * @param int $index
     * @return int
     * @throws Exception
     */
    public function getValueAt(int $index): int
    {
        if (!isset($this->list[$index])) {
            throw new Exception('Out of bounds');
        }
        return $this->list[$index];
    }
}