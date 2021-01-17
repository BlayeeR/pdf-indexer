import { Date } from '@models/Date';
import { Amount } from './Amount';
import { Info } from './Info';

export interface PdfFile {
    Id: number;
    Name: string;
    DateSearchRadius: number;
    AmountSearchRadius: number;
    InfoSearchRadius: number;
    Dates: Date[];
    Amounts: Amount[];
    Info: Info[];
}
