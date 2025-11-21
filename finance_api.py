#!/usr/bin/env python3
import pg8000
import json
from flask import Flask, jsonify, request
from flask_cors import CORS

app = Flask(__name__)
CORS(app)

# PostgreSQL connection
DB_CONFIG = {
    'host': '72.60.218.167',
    'port': 5432,
    'database': 'modernsap',
    'user': 'postgres',
    'password': 'mango'
}

def get_db_connection():
    return pg8000.connect(**DB_CONFIG)

@app.route('/api/finance/tables', methods=['GET'])
def get_tables():
    try:
        conn = get_db_connection()
        cur = conn.cursor()
        cur.execute("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'")
        tables = [row[0] for row in cur.fetchall()]
        cur.close()
        conn.close()
        return jsonify({'tables': tables})
    except Exception as e:
        return jsonify({'error': str(e)}), 500

@app.route('/api/finance/data/<table>', methods=['GET'])
def get_table_data(table):
    try:
        conn = get_db_connection()
        cur = conn.cursor()
        limit = request.args.get('limit', 100)
        cur.execute(f"SELECT * FROM {table} LIMIT %s", (limit,))
        columns = [desc[0] for desc in cur.description]
        rows = cur.fetchall()
        data = [dict(zip(columns, row)) for row in rows]
        cur.close()
        conn.close()
        return jsonify({'data': data, 'columns': columns})
    except Exception as e:
        return jsonify({'error': str(e)}), 500

@app.route('/api/finance/sync', methods=['POST'])
def sync_data():
    try:
        data = request.json
        table = data.get('table')
        mysql_endpoint = data.get('mysql_endpoint')
        
        # Fetch PostgreSQL data
        conn = get_db_connection()
        cur = conn.cursor()
        cur.execute(f"SELECT * FROM {table}")
        columns = [desc[0] for desc in cur.description]
        rows = cur.fetchall()
        finance_data = [dict(zip(columns, row)) for row in rows]
        cur.close()
        conn.close()
        
        return jsonify({'status': 'success', 'records': len(finance_data), 'data': finance_data})
    except Exception as e:
        return jsonify({'error': str(e)}), 500

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)