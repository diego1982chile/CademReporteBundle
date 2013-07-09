<?php

namespace Cadem\ReporteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Variable
 *
 * @ORM\Table(name="PARAMETRO")
 * @ORM\Entity
 */
class Parametro
{
    /**
     * @var integer
     *
     * @ORM\Column(name="ID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;
	
	/**
     * @var integer
     *
     * @ORM\Column(name="CLIENTE_ID", type="integer", nullable=false)
     *      
     */
    private $clienteid;	

    /**
     * @var string
     *
     * @ORM\Column(name="NOMBRE", type="string", length=64, nullable=false)
     */
    private $nombre;

    /**
     * @var int
     *
     * @ORM\Column(name="VALOR", type="string", length=256, nullable=true)
     */
    private $valor;

    /**
     * @var boolean
     *
     * @ORM\Column(name="ACTIVO", type="boolean", nullable=false)
     */
    private $activo;		 

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get nombre
     *
     * @return string 
     */
    public function getNombre()
    {
        return $this->nombre;
    }   

    /**
     * Set activo
     *
     * @param boolean $activo
     * @return Variable
     */
    public function setActivo($activo)
    {
        $this->activo = $activo;
    
        return $this;
    }

    /**
     * Get activo
     *
     * @return boolean 
     */
    public function getActivo()
    {
        return $this->activo;
    }    
}