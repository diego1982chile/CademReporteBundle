<?php

namespace Cadem\ReporteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Planogramap
 *
 * @ORM\Table(name="PLANOGRAMAP")
 * @ORM\Entity
 */
class Planogramap
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
     * @ORM\Column(name="MEDICION_ID", type="integer", nullable=true)
     */
    private $medicionid;
	
	/**
     * @var integer
     *
     * @ORM\Column(name="SALACLIENTE_ID", type="integer", nullable=true)
     */
    private $salaclienteid;
	
	/**
     * @var integer
     *
     * @ORM\Column(name="ITEMCLIENTE_ID", type="integer", nullable=true)
     */
    private $itemclienteid;

    /**
     * @var integer
     *
     * @ORM\Column(name="POLITICAPRECIO", type="integer", nullable=true)
     */
    private $politicaprecio;

    /**
     * @var boolean
     *
     * @ORM\Column(name="ACTIVO", type="boolean", nullable=false)
     */
    private $activo;

    /**
     * @var \Medicion
     *
     * @ORM\ManyToOne(targetEntity="Medicion", inversedBy="planogramaps")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="MEDICION_ID", referencedColumnName="ID")
     * })
     */
    private $medicion;

    /**
     * @var \Salacliente
     *
     * @ORM\ManyToOne(targetEntity="Salacliente", inversedBy="planogramaps")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="SALACLIENTE_ID", referencedColumnName="ID")
     * })
     */
    private $salacliente;
	
	/**
     * @var \Itemcliente
     *
     * @ORM\ManyToOne(targetEntity="Itemcliente", inversedBy="planogramaps")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ITEMCLIENTE_ID", referencedColumnName="ID")
     * })
     */
    private $itemcliente;


	/**
     * @ORM\OneToMany(targetEntity="Precio", mappedBy="planogramap")
     */
	 
	protected $precios;
	
	
	public function __construct()
    {
        $this->precios = new ArrayCollection();
    }
	

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
     * Get medicionid
     *
     * @return integer 
     */
    public function getMedicionId()
    {
        return $this->medicionid;
    }
	
	/**
     * Get salaclienteid
     *
     * @return integer 
     */
    public function getSalaclienteId()
    {
        return $this->salaclienteid;
    }
	
	/**
     * Get itemclienteid
     *
     * @return integer 
     */
    public function getItemclienteId()
    {
        return $this->itemclienteid;
    }
	
	/**
     * Set medicionid
     *
	 * @param integer $medicionid
     * @return Planogramap
     */
    public function SetMedicionId($medicionid)
    {
        $this->medicionid = $medicionid;
		
		return $this;
    }
	
	/**
     * Set salaclienteid
     *
	 * @param integer $salaclienteid
     * @return Planogramap
     */
    public function SetSalaclienteId($salaclienteid)
    {
        $this->salaclienteid = $salaclienteid;
		
		return $this;
    }
	
	/**
     * Set itemclienteid
     *
	 * @param integer $itemclienteid
     * @return Planogramap
     */
    public function SetItemclienteId($itemclienteid)
    {
        $this->itemclienteid = $itemclienteid;
		
		return $this;
    }

    /**
     * Set activo
     *
     * @param boolean $activo
     * @return Planogramap
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
     * Set medicion
     *
     * @param \Cadem\ReporteBundle\Entity\Medicion $medicion
     * @return Planogramap
     */
    public function setMedicion(\Cadem\ReporteBundle\Entity\Medicion $medicion = null)
    {
        $this->medicion = $medicion;
    
        return $this;
    }

    /**
     * Get medicion
     *
     * @return \Cadem\ReporteBundle\Entity\Medicion 
     */
    public function getMedicion()
    {
        return $this->medicion;
    }

    /**
     * Set salacliente
     *
     * @param \Cadem\ReporteBundle\Entity\Salacliente $salacliente
     * @return Planogramap
     */
    public function setSalacliente(\Cadem\ReporteBundle\Entity\Salacliente $salacliente = null)
    {
        $this->salacliente = $salacliente;
    
        return $this;
    }

    /**
     * Get salacliente
     *
     * @return \Cadem\ReporteBundle\Entity\Salacliente 
     */
    public function getSalacliente()
    {
        return $this->salacliente;
    }
	
	/**
     * Set itemcliente
     *
     * @param \Cadem\ReporteBundle\Entity\Itemcliente $itemcliente
     * @return Planogramap
     */
    public function setItemcliente(\Cadem\ReporteBundle\Entity\Itemcliente $itemcliente = null)
    {
        $this->itemcliente = $itemcliente;
    
        return $this;
    }

    /**
     * Get itemcliente
     *
     * @return \Cadem\ReporteBundle\Entity\Itemcliente 
     */
    public function getItemcliente()
    {
        return $this->itemcliente;
    }

    /**
     * Add precios
     *
     * @param \Cadem\ReporteBundle\Entity\Precio $precios
     * @return Planogramap
     */
    public function addPrecios(\Cadem\ReporteBundle\Entity\Precio $precios)
    {
        $this->precios[] = $precios;
    
        return $this;
    }

    /**
     * Remove precios
     *
     * @param \Cadem\ReporteBundle\Entity\Precio $precios
     */
    public function removePrecio(\Cadem\ReporteBundle\Entity\Precio $precios)
    {
        $this->precios->removeElement($precios);
    }

    /**
     * Get precios
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getPrecios()
    {
        return $this->precios;
    }
}