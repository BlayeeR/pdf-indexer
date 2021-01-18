export interface Amount {
    Gross: number;
    Vat: string;
    Net: number
}

export enum Vat {
  vat_zw = 0,
  vat_np = 1,
  vat_0 = 2,
  vat_5 = 3,
  vat_8 = 4,
  vat_23 =5
}
