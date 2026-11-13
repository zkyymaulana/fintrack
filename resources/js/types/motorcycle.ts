export type MotorcycleSummary = {
    id: number;
    name: string;
    brand: string | null;
    model: string | null;
    license_plate: string | null;
    current_odometer_km: number;
    active_maintenance_count: number;
    next_due_km: number | null;
    km_until_next_service: number | null;
};

export type Motorcycle = Pick<MotorcycleSummary, 'id' | 'name' | 'brand' | 'model' | 'license_plate' | 'current_odometer_km'>;

export type MotorcycleMaintenanceItem = {
    id: number;
    name: string;
    kind: 'part' | 'service';
    interval_km: number;
    baseline_odometer_km: number;
    note: string | null;
    is_active: boolean;
    last_serviced_km: number;
    next_due_km: number;
    km_remaining: number;
    status: 'due' | 'soon' | 'ok';
};

export type MotorcycleMaintenanceRecord = {
    id: number;
    title: string;
    odometer_km: number;
    performed_on: string;
    cost: string | null;
    note: string | null;
    wallet_name: string | null;
};

export type MotorcycleOdometerReading = {
    id: number;
    odometer_km: number;
    recorded_at: string;
    source: 'initial' | 'manual' | 'maintenance';
    note: string | null;
};
