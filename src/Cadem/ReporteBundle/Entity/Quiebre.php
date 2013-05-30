<?php

namespace Cadem\ReporteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Quiebre
 *
 * @ORM\Table(name="QUIEBRE")
 * @ORM\Entity
 */
class Quiebre
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
     * @var boolean
     *
     * @ORM\Column(name="HAYQUIEBRE", type="boolean", nullable=false)
     */
    private $hayquiebre;

    /**
     * @var integer
     *
     * @ORM\Column(name="CANTIDAD", type="integer", nullable=true)
     */
    private $cantidad;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="FECHAHORACAPTURA", type="datetime", nullable=true)
     */
    private $fechahoracaptura;

    /**
     * @var boolean
     *
     * @ORM\Column(name="ACTIVO", type="boolean", nullable=false)
     */
    private $activo;

    /**
     * @var \Planograma
     *
     * @ORM\ManyToOne(targetEntity="Planograma", inversedBy="quiebres")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="PLANOGRAMA_ID", referencedColumnName="ID")
     * })
     */
    private $planograma;
	


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
     * Set hayquiebre
     *
     * @param boolean $hayquiebre
     * @return Quiebre
     */
    public function setHayquiebre($hayquiebre)
    {
        $this->hayquiebre = $hayquiebre;
    
        return $this;
    }

    /**
     * Get hayquiebre
     *
     * @return boolean 
     */
    public function getHayquiebre()
    {
        return $this->hayquiebre;
    }

    /**
     * Set cantidad
     *
     * @param integer $cantidad
     * @return Quiebre
     */
    public function setCantidad($cantidad)
    {
        $this->cantidad = $cantidad;
    
        return $this;
    }

    /**
     * Get cantidad
     *
     * @return integer 
     */
    public function getCantidad()
    {
        return $this->cantidad;
    }

    /**
     * Set fechahoracaptura
     *
     * @param \DateTime $fechahoracaptura
     * @return Quiebre
     */
    public function setFechahoracaptura($fechahoracaptura)
    {
        $this->fechahoracaptura = $fechahoracaptura;
    
        return $this;
    }

    /**
     * Get fechahoracaptura
     *
     * @return \DateTime 
     */
    public function getFechahoracaptura()
    {
        return $this->fechahoracaptura;
    }

    /**
     * Set activo
     *
     * @param boolean $activo
     * @return Quiebre
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

    /**
     * Set planograma
     *
     * @param \Cadem\ReporteBundle\Entity\Planograma $planograma
     * @return Quiebre
     */
    public function setPlanograma(\Cadem\ReporteBundle\Entity\Planograma $planograma = null)
    {
        $this->planograma = $planograma;
    
        return $this;
    }

    /**
     * Get planograma
     *
     * @return \Cadem\ReporteBundle\Entity\Planograma 
     */
    public function getPlanograma()
    {
        return $this->planograma;
    }
}