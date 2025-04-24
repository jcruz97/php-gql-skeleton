<?php

namespace Vertuoza\Repositories\Settings\UnitTypes;

use Vertuoza\Repositories\BaseRepository;
use Vertuoza\Repositories\Settings\UnitTypes\Models\UnitTypeMapper;
use Vertuoza\Repositories\Settings\UnitTypes\Models\UnitTypeModel;

use function React\Async\async;

class UnitTypeRepository extends BaseRepository
{
  protected function getModelClass(): string
  {
    return UnitTypeModel::class;
  }
  
  protected function getMapperClass(): string
  {
    return UnitTypeMapper::class;
  }
  
  // Override the base filter method to handle the special tenant condition for UnitTypes
  protected function applyBaseFilters($query, string $tenantId)
  {
    $query->whereNull('deleted_at');
    
    $query->where(function ($q) use ($tenantId) {
      $q->where(UnitTypeModel::getTenantColumnName(), '=', $tenantId)
        ->orWhereNull(UnitTypeModel::getTenantColumnName());
    });
    
    return $query;
  }
  
  // Specific method for UnitType
  public function countUnitTypeWithLabel(string $name, string $tenantId, string|int|null $excludeId = null)
  {
    return async(
      fn () => $this->getQueryBuilder()
        ->where('label', $name)
        ->whereNull('deleted_at')
        ->where(function ($query) use ($excludeId) {
          if (isset($excludeId))
            $query->where('id', '!=', $excludeId);
        })
        ->where(function ($query) use ($tenantId) {
          $query->where(UnitTypeModel::getTenantColumnName(), '=', $tenantId)
            ->orWhereNull(UnitTypeModel::getTenantColumnName());
        })
    )();
  }

  // These could be in BaseRepository if we consider it handling mutations
  public function create(UnitTypeMutationData $data, string $tenantId): int|string
  {
    $newId = $this->getQueryBuilder()->insertGetId(
      UnitTypeMapper::serializeCreate($data, $tenantId)
    );
    return $newId;
  }

  public function update(string $id, UnitTypeMutationData $data)
  {
    $this->getQueryBuilder()
      ->where(UnitTypeModel::getPkColumnName(), $id)
      ->update(UnitTypeMapper::serializeUpdate($data));

    $this->clearCache($id);
  }
}
