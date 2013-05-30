<?php

namespace Cadem\ReporteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Planograma
 *
 * @ORM\Table(name="PLANOGRAMA")
 * @ORM\Entity
 */
class Planograma
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
     * @var boolean
     *
     * @ORM\Column(name="ACTIVO", type="boolean", nullable=false)
     */
    private $activo;

    /**
     * @var \Medicion
     *
     * @ORM\ManyToOne(targetEntity="Medicion", inversedBy="planogramas")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="MEDICION_ID", referencedColumnName="ID")
     * })
     */
    private $medicion;

    /**
     * @var \Salacliente
     *
     * @ORM\ManyToOne(targetEntity="Salacliente", inversedBy="planogramas")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="SALACLIENTE_ID", referencedColumnName="ID")
     * })
     */
    private $salacliente;
	
	/**
     * @var \Itemcliente
     *
     * @ORM\ManyToOne(targetEntity="Itemcliente", inversedBy="planogramas")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ITEMCLIENTE_ID", referencedColumnName="ID")
     * })
     */
    private $itemcliente;


	/**
     * @ORM\OneToMany(targetEntity="Quiebre", mappedBy="planograma")
     */
	 
	protected $quiebres;
	
	
	public function __construct()
    {
        $this->quiebres = new ArrayCollection();
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
     * @return Planograma
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
     * @return Planograma
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
     * @return Planograma
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
     * @return Planograma
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
     * @return Planograma
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
     * @return Planograma
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
     * @return Planograma
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
     * Add quiebres
     *
     * @param \Cadem\ReporteBundle\Entity\Quiebre $quiebres
     * @return Planograma
     */
    public function addQuiebre(\Cadem\ReporteBundle\Entity\Quiebre $quiebres)
    {
        $this->quiebres[] = $quiebres;
    
        return $this;
    }

    /**
     * Remove quiebres
     *
     * @param \Cadem\ReporteBundle\Entity\Quiebre $quiebres
     */
    public function removeQuiebre(\Cadem\ReporteBundle\Entity\Quiebre $quiebres)
    {
        $this->quiebres->removeElement($quiebres);
    }

    /**
     * Get quiebres
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getQuiebres()
    {
        return $this->quiebres;
    }
}