var Help = {
	
	Declension: function (Num, Titles) {
	    Cases = [2, 0, 1, 1, 1, 2];
	    return Titles[(Num % 100 > 4 && Num % 100 < 20) ? 2 : Cases[min(Num % 10, 5)]];
	}
	
}