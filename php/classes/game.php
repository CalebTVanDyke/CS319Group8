<?php

  require 'boardspace.php';
  require 'player.php';
  require_once 'connect.php';

  class Game
  {
  
    private $id;
    private $board = array(array());
    private $players = array();
    private $totalMoves;
    private $turn;
    private $created_at;
  
    public function __construct($players, $totalMoves, $turn, $id = null, $board = null, $created_at = null) 
    {  
      if(!is_array($players)) 
        throw new Exception('players paramater must be an array');
        
      if(is_null($board)) 
        $this->makeBlankBoard();

      $this->players = $players;
      $this->totalMoves = $totalMoves;
      $this->turn = $turn;
    }

    /**
     * Magic getter
     *
     * @param property to get
     * @return property or false if it does not exist
     */
    public function __get($property) 
    { 
      if (property_exists($this, $property)) 
          return $this->$property;

        return false;
    }

    /**
     * Magic setter
     *
     * @param property to set
     * @param value to set the property to
     * @return true on successfull set, false on fail
     */
    public function __set($property, $value) 
    { 
      if($property == 'id')
        throw new Exception("Please do not try to alter the id of the player.");

      if (property_exists($this, $property)) 
      {
          $this->$property = $value;
          return true;
        }

        return false;
    }

    public function push() 
    {
      $socket = new Connect();
      $con = $socket->getConnection();

      $addStmt = "INSERT INTO game (player1_id, player2_id, total_moves, turn, board) VALUES (:player1, :player2, :total_moves, :turn, :board)";
      $updateStmt = "UPDATE game SET player1_id=:player1, player2_id=:player2, total_moves=:total_moves, turn=:turn, board=:board WHERE id=:id";

      if(!isset($this->id)) 
      {
        // create a new game in the database
        $add = $con->prepare($addStmt);
        if( !$add->execute(array(':player1' => $this->players[0]->id, ':player2' => $this->players[1]->id, ':total_moves' => $this->totalMoves, ':turn' => $this->turn, ':board' => $this->serializeBoard()))) 
          throw new Exception("Could not add new Player to the database in push function.");

        $this->created_at = date('Y-m-d H:i:s');
        $this->id = $con->lastInsertId(); 
        session_start();
      } 
      else 
      {
        // update current player
        $current = Db::find($this->id, 'id', 'game');

        $update = $con->prepare($updateStmt);
        if(!$update->execute(array(':player1' => $this->players[0]->id, ':player2' => $this->players[1]->id, ':total_moves' => $this->total_moves, ':turn' => $this->turn, ':board' => $this->serializeBoard(), ':id' => $this->id))) 
          throw new Exception();
      }
    }
    
    public function serializeBoard() 
    {
      $jsonBoard = array(array());

      for($y = 0; $y < 12; $y+=1)
      {
        $row = array();
        
        for($x = 0; $x < 8; $x+=1) 
        {
          //BoardSpace::unserialize($this->board[$y][$x]);
          array_push($row, BoardSpace::unserialize($this->board[$y][$x])->serialize());    
        }

        array_push($jsonBoard, $row);
      }

      // first is always empty, so remove it
      array_shift($jsonBoard);
      return json_encode($jsonBoard);
    }

    /**
     * Initialize the board with BoardSpace's 
     **/
    private function makeBlankBoard() 
    {
      for($y = 0; $y < 12; $y+=1)
      {
        for($x = 0; $x < 8; $x+=1)
        {
          $this->board[$y][$x] = new BoardSpace();
        }
      }
    }

    public function serialize()
    {
      return json_encode(array(
        'id' => $this->id,
        'player1' => Player::unserialize($this->players[0])->serialize(),
        'player2' => Player::unserialize($this->players[1])->serialize(),
        'totalMoves' => $this->totalMoves,
        'turn' => $this->turn,
        'board' => $this->serializeBoard()
      ));
    }

    // TODO
    /*public static function unserialize($obj)
    {
      if(is_object($obj) && property_exists($obj, 'board')) 
      {
        $data = json_decode($data);
        //$newPlayer = new Player($obj->email, $obj->pass_hash, $obj->gamertag, $obj->theme_color, $obj->id, $obj->created_at);
        $newGame = new Game(
          array(
            $obj->player1,
            $obj->player2
          ),
          $obj->totalMoves,
          $obj->turn,
          $obj->id,
          $obj->board,
          $obj->created_at
        );

        return $newPlayer;
      } 
      else 
      {
        return false;
      }
    }*/
  }

?>
