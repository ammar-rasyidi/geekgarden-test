import { calculateFinalStock, FinalStock, StockMovement } from './stockCheck';

describe('checkFinalStock', () => {
  const testData: StockMovement[] = [
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

  it('should check correct final stock for all products', () => {
    const result = calculateFinalStock(testData);
    console.log("result", result);
    expect(result).toEqual({
      A: 6,
      B: 10,
      C: 50,
      D: 5,
      E: 10,
    });
  });

  it('should handle empty array', () => {
    expect(calculateFinalStock([])).toEqual({});
  });

  it('should handle only incoming stock', () => {
    const data: StockMovement[] = [
      { product: 'X', type: 'in', qty: 100 },
      { product: 'Y', type: 'in', qty: 50 },
    ];
    expect(calculateFinalStock(data)).toEqual({ X: 100, Y: 50 });
  });

  it('should handle negative stock', () => {
    const data: StockMovement[] = [
      { product: 'Z', type: 'out', qty: 10 },
    ];
    expect(calculateFinalStock(data)).toEqual({ Z: -10 });
  });
});
