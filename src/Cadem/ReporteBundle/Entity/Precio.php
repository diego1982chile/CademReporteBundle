<?php

namespace Cadem\ReporteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Precio
 *
 * @ORM\Table(name="Precio")
 * @ORM\Entity
 */
class Precio
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
     * @ORM\Column(name="PRECIO", type="integer", nullable=true)
     */
    private $precio;

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
     * @ORM\ManyToOne(targetEntity="Planogramap", inversedBy="precios")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="PLANOGRAMAP_ID", referencedColumnName="ID")
     * })
     */
    private $planogramap;
	


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
     * Set precio
     *
     * @param integer $precio
     * @return Precio
     */
    public function setPrecio($precio)
    {
        $this->precio = $precio;
    
        return $this;
    }

    /**
     * Get precio
     *
     * @return integer 
     */
    public function getPrecio()
    {
        return $this->precio;
    }

    /**
     * Set fechahoracaptura
     *
     * @param \DateTime $fechahoracaptura
     * @return Precio
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
     * @return Precio
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
     * Set planogramap
     *
     * @param \Cadem\ReporteBundle\Entity\Planogramap $planogramap
     * @return Precio
     */
    public function setPlanograma(\Cadem\ReporteBundle\Entity\Planogramap $planogramap = null)
    {
        $this->planogramap = $planogramap;
    
        return $this;
    }

    /**
     * Get planogramap
     *
     * @return \Cadem\ReporteBundle\Entity\Planograma p
     */
    public function getPlanogramap()
    {
        return $this->planogramap;
    }
}