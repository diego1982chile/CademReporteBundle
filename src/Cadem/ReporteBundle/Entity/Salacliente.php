<?php

namespace Cadem\ReporteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Salacliente
 *
 * @ORM\Table(name="SALACLIENTE")
 * @ORM\Entity
 */
class Salacliente
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
     * @ORM\Column(name="CLIENTE_ID", type="integer", nullable=true)
     */
    private $clienteid;

    /**
     * @var string
     *
     * @ORM\Column(name="CODIGOSALA", type="string", length=64, nullable=false)
     */
    private $codigosala;

    /**
     * @var boolean
     *
     * @ORM\Column(name="ACTIVO", type="boolean", nullable=false)
     */
    private $activo;

    /**
     * @var \Cliente
     *
     * @ORM\ManyToOne(targetEntity="Cliente", inversedBy="salaclientes")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="CLIENTE_ID", referencedColumnName="ID")
     * })
     */
    private $cliente;

    /**
     * @var \Empleado
     *
     * @ORM\ManyToOne(targetEntity="Empleado", inversedBy="salaclientes")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="EMPLEADO_ID", referencedColumnName="ID")
     * })
     */
    private $empleado;

    /**
     * @var \Sala
     *
     * @ORM\ManyToOne(targetEntity="Sala", inversedBy="salaclientes")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="SALA_ID", referencedColumnName="ID")
     * })
     */
    private $sala;

	/**
     * @ORM\OneToMany(targetEntity="Planograma", mappedBy="salacliente")
     */
	 
	protected $planogramas;
	
	
	public function __construct()
    {
        $this->planogramas = new ArrayCollection();
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
     * Get clienteid
     *
     * @return integer 
     */
    public function getClienteId()
    {
        return $this->clienteid;
    }
	
	/**
     * Set clienteid
     *
	 * @param integer $clienteid
     * @return Salacliente
     */
    public function SetClienteId($clienteid)
    {
        $this->clienteid = $clienteid;
		
		return $this;
    }

    /**
     * Set codigosala
     *
     * @param string $codigosala
     * @return Salacliente
     */
    public function setCodigosala($codigosala)
    {
        $this->codigosala = $codigosala;
    
        return $this;
    }

    /**
     * Get codigosala
     *
     * @return string 
     */
    public function getCodigosala()
    {
        return $this->codigosala;
    }

    /**
     * Set activo
     *
     * @param boolean $activo
     * @return Salacliente
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
     * Set cliente
     *
     * @param \Cadem\ReporteBundle\Entity\Cliente $cliente
     * @return Salacliente
     */
    public function setCliente(\Cadem\ReporteBundle\Entity\Cliente $cliente = null)
    {
        $this->cliente = $cliente;
    
        return $this;
    }

    /**
     * Get cliente
     *
     * @return \Cadem\ReporteBundle\Entity\Cliente 
     */
    public function getCliente()
    {
        return $this->cliente;
    }

    /**
     * Set empleado
     *
     * @param \Cadem\ReporteBundle\Entity\Empleado $empleado
     * @return Salacliente
     */
    public function setEmpleado(\Cadem\ReporteBundle\Entity\Empleado $empleado = null)
    {
        $this->empleado = $empleado;
    
        return $this;
    }

    /**
     * Get empleado
     *
     * @return \Cadem\ReporteBundle\Entity\Empleado 
     */
    public function getEmpleado()
    {
        return $this->empleado;
    }

    /**
     * Set sala
     *
     * @param \Cadem\ReporteBundle\Entity\Sala $sala
     * @return Salacliente
     */
    public function setSala(\Cadem\ReporteBundle\Entity\Sala $sala = null)
    {
        $this->sala = $sala;
    
        return $this;
    }

    /**
     * Get sala
     *
     * @return \Cadem\ReporteBundle\Entity\Sala 
     */
    public function getSala()
    {
        return $this->sala;
    }
	
	/**
     * Add planograma
     *
     * @param \Cadem\ReporteBundle\Entity\Planograma $planograma
     * @return Salacliente
     */
    public function addPlanograma(\Cadem\ReporteBundle\Entity\Planograma $planograma)
    {
        $this->planogramas[] = $planograma;
    
        return $this;
    }

    /**
     * Remove planograma
     *
     * @param \Cadem\ReporteBundle\Entity\Planograma $planograma
     */
    public function removePlanograma(\Cadem\ReporteBundle\Entity\Planograma $planograma)
    {
        $this->planogramas->removeElement($planograma);
    }

    /**
     * Get planogramas
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getPlanogramas()
    {
        return $this->planogramas;
    }
}