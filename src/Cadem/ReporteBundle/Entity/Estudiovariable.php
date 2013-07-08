<?php

namespace Cadem\ReporteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Estudiovariable
 *
 * @ORM\Table(name="ESTUDIOVARIABLE")
 * @ORM\Entity
 */
class Estudiovariable
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
     * @ORM\Column(name="VARIABLE_ID", type="integer", nullable=false)
     */
    private $variableid;

    /**
     * @var string
     *
     * @ORM\Column(name="NOMBREVARIABLE", type="string", length=64, nullable=false)
     */
    private $nombrevariable;

    /**
     * @var boolean
     *
     * @ORM\Column(name="ACTIVO", type="boolean", nullable=false)
     */
    private $activo;

    /**
     * @var \Estudio
     *
     * @ORM\ManyToOne(targetEntity="Estudio", inversedBy="estudiovariables")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ESTUDIO_ID", referencedColumnName="ID")
     * })
     */
    private $estudio;

    /**
     * @var \Variable
     *
     * @ORM\ManyToOne(targetEntity="Variable", inversedBy="estudiovariables")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="VARIABLE_ID", referencedColumnName="ID")
     * })
     */
    private $variable;

    /**
     * @var \Empleado
     *
     * @ORM\ManyToOne(targetEntity="Empleado", inversedBy="estudiovariables")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="EMPLEADO_ID", referencedColumnName="ID")
     * })
     */
    private $empleado;

    /**
     * @ORM\OneToMany(targetEntity="Medicion", mappedBy="estudio")
     */
     
    protected $mediciones;

    public function __construct()
    {
        $this->mediciones = new ArrayCollection();
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
     * Set variableid
     *
     * @param integer $variableid
     * @return Estudiovariable
     */
    public function setVariableId($variableid)
    {
        $this->variableid = $variableid;
    
        return $this;
    }

    /**
     * Get variableid
     *
     * @return integer 
     */
    public function getVariableId()
    {
        return $this->variableid;
    }

    /**
     * Set nombrevariable
     *
     * @param string $nombrevariable
     * @return Estudiovariable
     */
    public function setNombrevariable($nombrevariable)
    {
        $this->nombrevariable = $nombrevariable;
    
        return $this;
    }

    /**
     * Get nombrevariable
     *
     * @return string 
     */
    public function getNombrevariable()
    {
        return $this->nombrevariable;
    }

    /**
     * Set activo
     *
     * @param boolean $activo
     * @return Estudiovariable
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
     * Set estudio
     *
     * @param \Cadem\ReporteBundle\Entity\Estudio $estudio
     * @return Estudiovariable
     */
    public function setEstudio(\Cadem\ReporteBundle\Entity\Estudio $estudio = null)
    {
        $this->estudio = $estudio;
    
        return $this;
    }

    /**
     * Get estudio
     *
     * @return \Cadem\ReporteBundle\Entity\Estudio 
     */
    public function getEstudio()
    {
        return $this->estudio;
    }

    /**
     * Set empleado
     *
     * @param \Cadem\ReporteBundle\Entity\Empleado $empleado
     * @return Estudiovariable
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
     * Set variable
     *
     * @param \Cadem\ReporteBundle\Entity\Variable $variable
     * @return Estudiovariable
     */
    public function setVariable(\Cadem\ReporteBundle\Entity\Variable $variable = null)
    {
        $this->variable = $variable;
    
        return $this;
    }

    /**
     * Get variable
     *
     * @return \Cadem\ReporteBundle\Entity\Variable 
     */
    public function getVariable()
    {
        return $this->variable;
    }

    /**
     * Add mediciones
     *
     * @param \Cadem\ReporteBundle\Entity\Medicion $mediciones
     * @return Estudiovariable
     */
    public function addMedicione(\Cadem\ReporteBundle\Entity\Medicion $mediciones)
    {
        $this->mediciones[] = $mediciones;
    
        return $this;
    }

    /**
     * Remove mediciones
     *
     * @param \Cadem\ReporteBundle\Entity\Medicion $mediciones
     */
    public function removeMedicione(\Cadem\ReporteBundle\Entity\Medicion $mediciones)
    {
        $this->mediciones->removeElement($mediciones);
    }

    /**
     * Get mediciones
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getMediciones()
    {
        return $this->mediciones;
    }
}