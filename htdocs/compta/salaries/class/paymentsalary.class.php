<?php
/* Copyright (C) 2011-2014 Alexandre Spangaro   <alexandre.spangaro@gmail.com>
 * Copyright (C) 2014	   Juanjo Menent		<jmenent@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/compta/salaries/class/paymentsalary.class.php
 *      \ingroup    salaries
 *      \brief		Class for salaries module payment
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT .'/core/class/commonobject.class.php';


/**
 *  Class to manage salary payments
 */
class PaymentSalary extends CommonObject
{
	//public $element='payment_salary';			//!< Id that identify managed objects
	//public $table_element='payment_salary';	//!< Name of table without prefix where object is stored

	var $id;
	var $ref;

	var $tms;
	var $fk_user;
	var $datep;
	var $datev;
	var $amount;
	var $type_payment;
	var $num_payment;
	var $label;
	var $datesp;
	var $dateep;
	var $note;
	var $fk_bank;
	var $fk_user_creat;
	var $fk_user_modif;


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
		$this->element = 'payment_salary';
		$this->table_element = 'payment_salary';
		return 1;
	}

	/**
	 * Update database
	 *
	 * @param   User	$user        	User that modify
	 * @param	int		$notrigger	    0=no, 1=yes (no update trigger)
	 * @return  int         			<0 if KO, >0 if OK
	 */
	function update($user=null, $notrigger=0)
	{
		global $conf, $langs;

		$error=0;

		// Clean parameters
		$this->fk_user=trim($this->fk_user);
		$this->amount=trim($this->amount);
		$this->label=trim($this->label);
		$this->note=trim($this->note);
		$this->fk_bank=trim($this->fk_bank);
		$this->fk_user_creat=trim($this->fk_user_creat);
		$this->fk_user_modif=trim($this->fk_user_modif);

		// Check parameters
		if (empty($this->fk_user) || $this->fk_user < 0)
		{
			$this->error='ErrorBadParameter';
			return -1;
		}

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."payment_salary SET";

		$sql.= " tms=".$this->db->idate($this->tms).",";
		$sql.= " fk_user='".$this->fk_user."',";
		$sql.= " datep=".$this->db->idate($this->datep).",";
		$sql.= " datev=".$this->db->idate($this->datev).",";
		$sql.= " amount='".$this->amount."',";
		$sql.= " fk_typepayment=".$this->fk_typepayment."',";
		$sql.= " num_payment='".$this->num_payment."',";
		$sql.= " label='".$this->db->escape($this->label)."',";
		$sql.= " datesp=".$this->db->idate($this->datesp).",";
		$sql.= " dateep=".$this->db->idate($this->dateep).",";
		$sql.= " note='".$this->db->escape($this->note)."',";
		$sql.= " fk_bank=".($this->fk_bank > 0 ? "'".$this->fk_bank."'":"null").",";
		$sql.= " fk_user_creat='".$this->fk_user_creat."',";
		$sql.= " fk_user_modif='".$this->fk_user_modif."'";

		$sql.= " WHERE rowid=".$this->id;

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql)
		{
			$this->error="Error ".$this->db->lasterror();
			return -1;
		}

		if (! $notrigger)
		{
            // Call trigger
            $result=$this->call_trigger('PAYMENT_SALARY_MODIFY',$user);
            if ($result < 0) $error++;
            // End call triggers

			//FIXME: Add rollback if trigger fail
		}

		return 1;
	}


	/**
	 *  Load object in memory from database
	 *
	 *  @param	int		$id         id object
	 *  @param  User	$user       User that load
	 *  @return int         		<0 if KO, >0 if OK
	 */
	function fetch($id, $user=null)
	{
		global $langs;
		$sql = "SELECT";
		$sql.= " s.rowid,";

		$sql.= " s.tms,";
		$sql.= " s.fk_user,";
		$sql.= " s.datep,";
		$sql.= " s.datev,";
		$sql.= " s.amount,";
		$sql.= " s.fk_typepayment,";
		$sql.= " s.num_payment,";
		$sql.= " s.label,";
		$sql.= " s.datesp,";
		$sql.= " s.dateep,";
		$sql.= " s.note,";
		$sql.= " s.fk_bank,";
		$sql.= " s.fk_user_creat,";
		$sql.= " s.fk_user_modif,";
		$sql.= " b.fk_account,";
		$sql.= " b.fk_type,";
		$sql.= " b.rappro";

		$sql.= " FROM ".MAIN_DB_PREFIX."payment_salary as s";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank as b ON s.fk_bank = b.rowid";
		$sql.= " WHERE s.rowid = ".$id;

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);

				$this->id    = $obj->rowid;
				$this->ref   = $obj->rowid;
				$this->tms   = $this->db->jdate($obj->tms);
				$this->fk_user = $obj->fk_user;
				$this->datep = $this->db->jdate($obj->datep);
				$this->datev = $this->db->jdate($obj->datev);
				$this->amount = $obj->amount;
				$this->type_payement = $obj->fk_typepayment;
				$this->num_payment = $obj->num_payment;
				$this->label = $obj->label;
				$this->datesp = $this->db->jdate($obj->datesp);
				$this->dateep = $this->db->jdate($obj->dateep);
				$this->note  = $obj->note;
				$this->fk_bank = $obj->fk_bank;
				$this->fk_user_creat = $obj->fk_user_creat;
				$this->fk_user_modif = $obj->fk_user_modif;
				$this->fk_account = $obj->fk_account;
				$this->fk_type = $obj->fk_type;
				$this->rappro  = $obj->rappro;
			}
			$this->db->free($resql);

			return 1;
		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			return -1;
		}
	}


	/**
	 *  Delete object in database
	 *
	 *	@param	User	$user       User that delete
	 *	@return	int					<0 if KO, >0 if OK
	 */
	function delete($user)
	{
		global $conf, $langs;

		$error=0;

		// Call trigger
		$result=$this->call_trigger('PAYMENT_SALARY_DELETE',$user);
		if ($result < 0) return -1;
		// End call triggers


		$sql = "DELETE FROM ".MAIN_DB_PREFIX."payment_salary";
		$sql.= " WHERE rowid=".$this->id;

		dol_syslog(get_class($this)."::delete", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql)
		{
			$this->error="Error ".$this->db->lasterror();
			return -1;
		}

		return 1;
	}


	/**
	 *  Initialise an instance with random values.
	 *  Used to build previews or test instances.
	 *	id must be 0 if object instance is a specimen.
	 *
	 *  @return	void
	 */
	function initAsSpecimen()
	{
		$this->id=0;

		$this->tms='';
		$this->fk_user='';
		$this->datep='';
		$this->datev='';
		$this->amount='';
		$this->label='';
		$this->datesp='';
		$this->dateep='';
		$this->note='';
		$this->fk_bank='';
		$this->fk_user_creat='';
		$this->fk_user_modif='';
	}

    /**
     *  Create in database
     *
     *  @param      User	$user       User that create
     *  @return     int      			<0 if KO, >0 if OK
     */
	function create($user)
	{
		global $conf,$langs;

		$error=0;

		// Clean parameters
		$this->amount=price2num(trim($this->amount));
		$this->label=trim($this->label);
		$this->note=trim($this->note);
		$this->fk_bank=trim($this->fk_bank);
		$this->fk_user_creat=trim($this->fk_user_creat);
		$this->fk_user_modif=trim($this->fk_user_modif);

		// Check parameters
		if (! $this->label)
		{
			$this->error=$langs->trans("ErrorFieldRequired",$langs->transnoentities("Label"));
			return -3;
		}
		if ($this->fk_user < 0 || $this->fk_user == '')
		{
			$this->error=$langs->trans("ErrorFieldRequired",$langs->transnoentities("Employee"));
			return -4;
		}
		if ($this->amount < 0 || $this->amount == '')
		{
			$this->error=$langs->trans("ErrorFieldRequired",$langs->transnoentities("Amount"));
			return -5;
		}
		if (! empty($conf->banque->enabled) && (empty($this->accountid) || $this->accountid <= 0))
		{
			$this->error=$langs->trans("ErrorFieldRequired",$langs->transnoentities("Account"));
			return -6;
		}
		if (! empty($conf->banque->enabled) && (empty($this->type_payment) || $this->type_payment <= 0))
		{
			$this->error=$langs->trans("ErrorFieldRequired",$langs->transnoentities("PaymentMode"));
			return -7;
		}

		$this->db->begin();

		// Insert into llx_payment_salary
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."payment_salary (fk_user";
		$sql.= ", datep";
		$sql.= ", datev";
		$sql.= ", amount";
		$sql.= ", salary";
		$sql.= ", fk_typepayment";
		$sql.= ", num_payment";
		if ($this->note) $sql.= ", note";
		$sql.= ", label";
		$sql.= ", datesp";
		$sql.= ", dateep";
		$sql.= ", fk_user_creat";
		$sql.= ", fk_bank";
		$sql.= ", entity";
		$sql.= ") ";
		$sql.= " VALUES (";
		$sql.= "'".$this->fk_user."'";
		$sql.= ", '".$this->db->idate($this->datep)."'";
		$sql.= ", '".$this->db->idate($this->datev)."'";
		$sql.= ", ".$this->amount;
		$sql.= ", ".($this->salary > 0 ? $this->salary : "null");
		$sql.= ", '".$this->type_payment."'";
		$sql.= ", '".$this->num_payment."'";
		if ($this->note) $sql.= ", '".$this->db->escape($this->note)."'";
		$sql.= ", '".$this->db->escape($this->label)."'";
		$sql.= ", '".$this->db->idate($this->datesp)."'";
		$sql.= ", '".$this->db->idate($this->dateep)."'";
		$sql.= ", '".$user->id."'";
		$sql.= ", NULL";
		$sql.= ", ".$conf->entity;
		$sql.= ")";

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{

			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."payment_salary");

			if ($this->id > 0)
			{
				if (! empty($conf->banque->enabled) && ! empty($this->amount))
				{
					// Insert into llx_bank
					require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

					$acc = new Account($this->db);
					$result=$acc->fetch($this->accountid);
					if ($result <= 0) dol_print_error($this->db);

					// Insert payment into llx_bank
					// Add link 'payment_salary' in bank_url between payment and bank transaction
					$bank_line_id = $acc->addline(
						$this->datep,
						$this->type_payment,
						$this->label,
						-abs($this->amount),
						$this->num_payment,
						'',
						$user
					);

					// Update fk_bank into llx_paiement.
					// So we know the payment which has generate the banking ecriture
					if ($bank_line_id > 0)
					{
						$this->update_fk_bank($bank_line_id);
					}
					else
					{
						$this->error=$acc->error;
						$error++;
					}

					if (! $error)
					{
						// Add link 'payment_salary' in bank_url between payment and bank transaction
						$url=DOL_URL_ROOT.'/compta/salaries/card.php?id=';

						$result=$acc->add_url_line($bank_line_id, $this->id, $url, "(SalaryPayment)", "payment_salary");
						if ($result <= 0)
						{
							$this->error=$acc->error;
							$error++;
						}
					}

					$fuser=new User($this->db);
					$fuser->fetch($this->fk_user);

					// Add link 'user' in bank_url between operation and bank transaction
					$result=$acc->add_url_line(
						$bank_line_id,
						$this->fk_user,
						DOL_URL_ROOT.'/user/card.php?id=',
						$langs->trans("SalaryPayment").' '.$fuser->getFullName($langs).' '.dol_print_date($this->datesp,'dayrfc').' '.dol_print_date($this->dateep,'dayrfc'),
						'user'
					);

					if ($result <= 0)
					{
						$this->error=$acc->error;
						$error++;
					}
				}

	            // Call trigger
	            $result=$this->call_trigger('PAYMENT_SALARY_CREATE',$user);
	            if ($result < 0) $error++;
	            // End call triggers

			}
			else $error++;

			if (! $error)
			{
				$this->db->commit();
				return $this->id;
			}
			else
			{
				$this->db->rollback();
				return -2;
			}
		}
		else
		{
			$this->error=$this->db->error();
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *  Update link between payment salary and line generate into llx_bank
	 *
	 *  @param	int		$id_bank    Id bank account
	 *	@return	int					<0 if KO, >0 if OK
	 */
	function update_fk_bank($id_bank)
	{
		$sql = 'UPDATE '.MAIN_DB_PREFIX.'payment_salary SET fk_bank = '.$id_bank;
		$sql.= ' WHERE rowid = '.$this->id;
		$result = $this->db->query($sql);
		if ($result)
		{
			return 1;
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}


	/**
	 *	Send name clicable (with possibly the picto)
	 *
	 *	@param	int		$withpicto		0=No picto, 1=Include picto into link, 2=Only picto
	 *	@param	string	$option			link option
	 *	@return	string					Chaine with URL
	 */
	function getNomUrl($withpicto=0,$option='')
	{
		global $langs;

		$result='';
        $label=$langs->trans("ShowSalaryPayment").': '.$this->ref;

        $lien = '<a href="'.DOL_URL_ROOT.'/compta/salaries/card.php?id='.$this->id.'" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
		$lienfin='</a>';

		$picto='payment';

        if ($withpicto) $result.=($lien.img_object($label, $picto, 'class="classfortooltip"').$lienfin);
		if ($withpicto && $withpicto != 2) $result.=' ';
		if ($withpicto != 2) $result.=$lien.$this->ref.$lienfin;
		return $result;
	}

}
