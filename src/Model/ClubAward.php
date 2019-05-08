<?php
/**
 * Created by IntelliJ IDEA.
 * User: stephan
 * Date: 27.01.18
 * Time: 17:54
 */

namespace KKL\Ligatool\Model;

/**
 * @SWG\Definition(required={"code", "name"}, type="object")
 * @ORM_Type              Entity
 * @ORM_Table "kkl_club_has_awards"
 * @ORM_AllowSchemaUpdate True
 */
class ClubAward extends KKLModel {

  /**
   * @var int
   * @SWG\Property(format="int64")
   * @ORM_Column_Type   int
   * @ORM_Column_Null   NULL
   */
  private $club_id;

  /**
   * @var int
   * @SWG\Property(format="int64")
   * @ORM_Column_Type   int
   * @ORM_Column_Null   NULL
   */
  private $award_id;
  
  
}
