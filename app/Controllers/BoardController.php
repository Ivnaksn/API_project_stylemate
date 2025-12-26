<?php

namespace App\Controllers;

use App\Models\BoardModel;
use App\Models\OutfitModel;
use App\Models\BoardOutfitModel;
use CodeIgniter\RESTful\ResourceController;

class BoardController extends ResourceController
{
    protected $format = 'json';
    public function index()
    {
        $boardModel = new BoardModel();
        $boards = $boardModel->findAll();

        return $this->respond($boards);
    }
    public function create()
    {
        $data = $this->request->getJSON(true);

        if (!isset($data['name']) || empty($data['name'])) {
            return $this->failValidationErrors('Board name is required');
        }

        $boardModel = new BoardModel();
        $boardModel->insert([
            'name' => $data['name'],
            'is_permanent' => false,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return $this->respondCreated([
            'message' => 'Board created successfully'
        ]);
    }
    public function semuaOutfits()
    {
        $outfitModel = new OutfitModel();
        $outfits = $outfitModel->findAll();

        return $this->respond($outfits);
    }
    public function boardOutfits($boardId)
    {
        $db = db_connect();

        $outfits = $db->table('board_outfits')
            ->select('outfits.*')
            ->join('outfits', 'outfits.id = board_outfits.outfit_id')
            ->where('board_outfits.board_id', $boardId)
            ->get()
            ->getResultArray();

        return $this->respond($outfits);
    }

    public function addOutfitToBoard($boardId)
    {
        $data = $this->request->getJSON(true);

        if (!isset($data['outfit_id'])) {
            return $this->failValidationErrors('outfit_id is required');
        }

        $boardOutfitModel = new BoardOutfitModel();
        $boardOutfitModel->insert([
            'board_id' => $boardId,
            'outfit_id' => $data['outfit_id']
        ]);

        return $this->respondCreated([
            'message' => 'Outfit added to board'
        ]);
    }
    public function delete($id = null)
    {
        $boardModel = new BoardModel();
        $board = $boardModel->find($id);

        if (!$board) {
            return $this->failNotFound('Board not found');
        }

        if ($board['is_permanent']) {
            return $this->failForbidden('Permanent board cannot be deleted');
        }

        $boardOutfitModel = new BoardOutfitModel();
        $boardOutfitModel->where('board_id', $id)->delete();
        $boardModel->delete($id);

        return $this->respondDeleted([
            'message' => 'Board deleted successfully'
        ]);
    }
}
