/**
 * Types for stock data
 */
export interface StockMovement {
  product: string;
  type: 'in' | 'out';
  qty: number;
}

export interface FinalStock {
  [product: string]: number;
}

/**
 * Calculate final stock per product from stock movements
 * 
 * @param movements - Array of stock movements (in/out transactions)
 * @returns Object with product names as keys and final stock quantities as values
 */
export function calculateFinalStock(movements: StockMovement[]): FinalStock {
  return movements.reduce((stock: FinalStock, movement) => {
    const { product, type, qty } = movement;
    
    // Check product stock if not exists
    if (!(product in stock)) {
      stock[product] = 0;
    }
    
    // Update stock based on movement type
    if (type === 'in') {
      stock[product] += qty;
    } else if (type === 'out') {
      stock[product] -= qty;
    }
    
    return stock;
  }, {});
}

const data: StockMovement[] = [
  { product: 'A', type: 'in', qty: 10 },
  { product: 'A', type: 'out', qty: 4 },
  { product: 'B', type: 'in', qty: 20 },
  { product: 'B', type: 'out', qty: 10 },
  { product: 'C', type: 'in', qty: 100 },
  { product: 'C', type: 'out', qty: 50 },
  { product: 'D', type: 'in', qty: 10 },
  { product: 'D', type: 'out', qty: 5 },
  { product: 'E', type: 'in', qty: 10 },
];

console.log('Input Data:', data);
console.log('Final Stock:', calculateFinalStock(data));