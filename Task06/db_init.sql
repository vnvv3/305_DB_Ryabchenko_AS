PRAGMA foreign_keys = ON;

DROP TABLE IF EXISTS work_execution_acts;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS client_orders;
DROP TABLE IF EXISTS weekly_schedule;
DROP TABLE IF EXISTS service_catalog;
DROP TABLE IF EXISTS staff_members;
DROP TABLE IF EXISTS staff_roles;

CREATE TABLE staff_roles (
    code TEXT PRIMARY KEY,
    title TEXT NOT NULL
);

CREATE TABLE staff_members (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    full_name TEXT NOT NULL,
    role_code TEXT NOT NULL DEFAULT 'MECH',
    phone_number TEXT,
    email TEXT UNIQUE,
    hire_date DATE NOT NULL,
    termination_date DATE,
    commission_rate REAL NOT NULL CHECK(commission_rate BETWEEN 0 AND 1),
    is_active INTEGER NOT NULL DEFAULT 1 CHECK(is_active IN (0, 1)),
    FOREIGN KEY (role_code) REFERENCES staff_roles(code)
);

CREATE TABLE service_catalog (
    service_code TEXT PRIMARY KEY,
    title TEXT NOT NULL,
    norm_hour REAL NOT NULL CHECK(norm_hour > 0),
    base_price REAL NOT NULL CHECK(base_price >= 0)
);

CREATE TABLE weekly_schedule (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    staff_id INTEGER NOT NULL,
    weekday_idx INTEGER NOT NULL CHECK(weekday_idx BETWEEN 0 AND 6),
    shift_start TIME NOT NULL,
    shift_end TIME NOT NULL,
    FOREIGN KEY (staff_id) REFERENCES staff_members(id) ON DELETE CASCADE
);

CREATE TABLE client_orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    master_id INTEGER NOT NULL,
    client_name TEXT NOT NULL,
    client_contact TEXT,
    visit_date DATE NOT NULL,
    visit_time TIME NOT NULL,
    order_status TEXT NOT NULL DEFAULT 'created' CHECK(order_status IN ('created', 'in_progress', 'closed', 'canceled')),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (master_id) REFERENCES staff_members(id) ON DELETE RESTRICT
);

CREATE TABLE order_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL,
    service_code TEXT NOT NULL,
    fixed_price REAL NOT NULL CHECK(fixed_price >= 0),
    FOREIGN KEY (order_id) REFERENCES client_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (service_code) REFERENCES service_catalog(service_code)
);

CREATE TABLE work_execution_acts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_item_id INTEGER NOT NULL UNIQUE,
    performer_id INTEGER NOT NULL,
    executed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    final_revenue REAL NOT NULL CHECK(final_revenue >= 0),
    FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE RESTRICT,
    FOREIGN KEY (performer_id) REFERENCES staff_members(id)
);

CREATE INDEX idx_staff_active ON staff_members(is_active);
CREATE INDEX idx_orders_date ON client_orders(visit_date);
CREATE INDEX idx_acts_performer ON work_execution_acts(performer_id);
CREATE INDEX idx_acts_date ON work_execution_acts(executed_at);

INSERT INTO staff_roles (code, title) VALUES 
('SENIOR', 'Старший смены'),
('MECH', 'Автомеханик'),
('ELEC', 'Автоэлектрик'),
('TIRE', 'Шиномонтажник');

INSERT INTO service_catalog (service_code, title, norm_hour, base_price) VALUES
('OIL_CHANGE', 'Экспресс-замена масла', 0.5, 800.00),
('FULL_DIAG', 'Комплексная диагностика', 1.0, 2500.00),
('TIRE_SWAP_R16', 'Шиномонтаж R16 (комплект)', 0.8, 2200.00),
('BRAKE_PADS', 'Замена тормозных колодок', 1.2, 1800.00),
('ENG_REPAIR', 'Капитальный ремонт ДВС', 24.0, 45000.00),
('ELEC_FIX', 'Ремонт проводки', 2.0, 3500.00);

INSERT INTO staff_members (full_name, role_code, phone, email, hire_date, termination_date, commission_rate, is_active) VALUES
('Климов Константин', 'SENIOR', '+7(900)111-22-33', 'klimov@sto.local', '2024-01-15', NULL, 0.40, 1),
('Власов Виталий', 'MECH', '+7(900)222-33-44', 'vlasov@sto.local', '2024-03-01', NULL, 0.35, 1),
('Громов Григорий', 'ELEC', '+7(900)333-44-55', 'gromov@sto.local', '2025-05-10', NULL, 0.35, 1),
('Дронов Денис', 'TIRE', '+7(900)444-55-66', 'dronov@sto.local', '2024-11-01', '2025-10-01', 0.30, 0),
('Ежов Евгений', 'MECH', '+7(900)555-66-77', 'ezhov@sto.local', '2025-02-20', NULL, 0.30, 1);

INSERT INTO weekly_schedule (staff_id, weekday_idx, shift_start, shift_end) VALUES
(1, 0, '09:00', '18:00'), (1, 1, '09:00', '18:00'), (1, 2, '09:00', '18:00'),
(2, 2, '10:00', '20:00'), (2, 3, '10:00', '20:00'), (2, 4, '10:00', '20:00'),
(3, 0, '12:00', '21:00'), (3, 4, '12:00', '21:00');

INSERT INTO client_orders (id, master_id, client_name, visit_date, visit_time, order_status) VALUES
(1, 1, 'Захаров Захар', '2025-12-10', '10:00', 'closed'),
(2, 2, 'Игнатьев Игорь', '2025-12-10', '14:30', 'closed'),
(3, 3, 'Кириллов Кирилл', '2025-12-11', '09:00', 'in_progress'),
(4, 1, 'Леонидов Леонид', '2025-12-12', '11:00', 'created');

INSERT INTO order_items (id, order_id, service_code, fixed_price) VALUES
(1, 1, 'OIL_CHANGE', 800.00),
(2, 1, 'FULL_DIAG', 2500.00),
(3, 2, 'BRAKE_PADS', 1800.00),
(4, 3, 'ELEC_FIX', 3500.00),
(5, 4, 'TIRE_SWAP_R16', 2200.00);

INSERT INTO work_execution_acts (order_item_id, performer_id, executed_at, final_revenue) VALUES
(1, 1, '2025-12-10 10:40:00', 800.00),
(2, 1, '2025-12-10 11:30:00', 2500.00),
(3, 2, '2025-12-10 16:00:00', 1800.00);
