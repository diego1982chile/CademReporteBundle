<?php

namespace Cadem\ReporteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Medicion
 *
 * @ORM\Table(name="MEDICION")
 * @ORM\Entity
 */
class Medicion
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
     * @var string
     *
     * @ORM\Column(name="NOMBRE", type="string", length=64, nullable=false)
     */
    private $nombre;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="FECHAINICIO", type="datetime", nullable=false)
     */
    private $fechainicio;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="FECHAFIN", type="datetime", nullable=false)
     */
    private $fechafin;

    /**
     * @var boolean
     *
     * @ORM\Column(name="ACTIVO", type="boolean", nullable=false)
     */
    private $activo;

    /**
     * @var \Estudiovariable
     *
     * @ORM\ManyToOne(targetEntity="Estudiovariable", inversedBy="mediciones")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ESTUDIOVARIABLE_ID", referencedColumnName="ID")
     * })
     */
    private $estudiovariable;
	
	/**
     * @ORM\OneToMany(targetEntity="Planogramap", mappedBy="medicion")
     */
	 
	protected $planogramaps;

	
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
     * Set nombre
     *
     * @param string $nombre
     * @return Medicion
     */
    public function setNombre($nombre)
    {
        $this->nombre = $nombre;
    
        return $this;
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
     * Set fechainicio
     *
     * @param \DateTime $fechainicio
     * @return Medicion
     */
    public function setFechainicio($fechainicio)
    {
        $this->fechainicio = $fechainicio;
    
        return $this;
    }

    /**
     * Get fechainicio
     *
     * @return \DateTime 
     */
    public function getFechainicio()
    {
        return $this->fechainicio;
    }

    /**
     * Set fechafin
     *
     * @param \DateTime $fechafin
     * @return Medicion
     */
    public function setFechafin($fechafin)
    {
        $this->fechafin = $fechafin;
    
        return $this;
    }

    /**
     * Get fechafin
     *
     * @return \DateTime 
     */
    public function getFechafin()
    {
        return $this->fechafin;
    }

    /**
     * Set activo
     *
     * @param boolean $activo
     * @return Medicion
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
     * Set estudiovariable
     *
     * @param \Cadem\ReporteBundle\Entity\Estudiovariable $estudiovariable
     * @return Medicion
     */
    public function setEstudiovariable(\Cadem\ReporteBundle\Entity\Estudiovariable $estudiovariable = null)
    {
        $this->estudiovariable = $estudiovariable;
    
        return $this;
    }

    /**
     * Get estudiovariable
     *
     * @return \Cadem\ReporteBundle\Entity\Estudiovariable 
     */
    public function getEstudiovariable()
    {
        return $this->estudiovariable;
    }

    /**
     * Add planogramap
     *
     * @param \Cadem\ReporteBundle\Entity\Planogramap $planogramap
     * @return Medicion
     */
    public function addPlanograma(\Cadem\ReporteBundle\Entity\Planogramap $planogramap)
    {
        $this->planogramaps[] = $planogramap;
    
        return $this;
    }

    /**
     * Remove planogramap
     *
     * @param \Cadem\ReporteBundle\Entity\Planogramap $planogramap
     */
    public function removePlanogramap(\Cadem\ReporteBundle\Entity\Planogramap $planogramap)
    {
        $this->planogramaps->removeElement($planogramap);
    }

    /**
     * Get planogramas
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getPlanogramaps()
    {
        return $this->planogramaps;
    }
}